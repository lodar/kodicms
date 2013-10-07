<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * @package    Kodi/Datasource
 */

class DataSource_Data_Hybrid_Section extends Datasource_Section {
	
	/**
	 *
	 * @var string
	 */
	public $ds_table = 'dshybrid';
	
	/**
	 *
	 * @var string
	 */
	public $ds_type = 'hybrid';
	
	/**
	 *
	 * @var string
	 */
	public $key;
	
	/**
	 *
	 * @var integer
	 */
	public $parent;
	
	/**
	 *
	 * @var string
	 */
	public $path = NULL;
	
	/**
	 *
	 * @var boolean
	 */
	public $doc_order = array(
		array('created_on' => 'desc')
	);

	/**
	 *
	 * @var DataSource_Data_Hybrid_Record
	 */
	public $record = NULL;
	public $read_sql = NULL;
	public $indexed_doc;
	public $doc_intro;
	public $indexed_doc_query;
	
	public $all_doc = TRUE;
	public $auto_cast = TRUE;
	
	public $fields = NULL;

	public function __construct($key = NULL, $parent = NULL)
	{
		$this->key = $key;
		$this->parent = $parent;
		
		$this->all_doc = Cookie::get('all_doc', 'enabled') != 'disabled';
		$this->auto_cast = Cookie::get('auto_cast', 'enabled') != 'disabled';
		$this->page_size = Cookie::get('page_size', 30);
	}
	
	/**
	 * 
	 * @return array
	 */
	public function fields( )
	{
		$this->fields = array(
//			'id' => array(
//				'name' => 'ID',
//				'width' => 50
//			),
			'header' => array(
				'name' => 'Header',
				'width' => NULL,
				'type' => 'link'
			),
		);
		
		if(!$this->all_doc)
		{
			unset($this->fields['type']);
		}
		
		$fields = DataSource_Data_Hybrid_Field_Factory::get_related_fields($this->ds_id);
		
		foreach($fields as $key => $field)
		{
			if(!$field->in_headline) continue;

			$this->fields[$field->name] = array(
				'name' =>  $field->header
			);
		}
		
		$this->fields['date'] = array(
			'name' => 'Date of creation',
			'width' => 150
		);
		
//		$this->fields['type'] = array(
//			'name' => 'Section',
//			'width' => 150
//		);
		
		return $this->fields;
	}

	/**
	 * 
	 * @return \DataSource_Data_Hybrid_Section
	 */
	public function remove() 
	{
		$ids = DB::select('id')
			->from('dshybrid')
			->where('ds_id', '=', $this->ds_id)
			->execute()
			->as_array(NULL, 'id');
		
		$this->remove_own_documents($ids);
	
		DB::delete('datasources')
			->where('ds_id', '=', $this->ds_id)
			->execute();

		$record = $this->get_record();
		$record->destroy();
		
		return $this;
	}
	
	/**
	 * 
	 * @param Datasource_Document $doc
	 * @return Datasource_Document
	 */
	public function create_document($doc) 
	{
		$id = $this->create_empty_document($doc->header);
		$doc->id = $id;

		$record = $this->get_record();
		$record->initialize_document($doc);
		$query = $record->get_sql($doc);

		$success = TRUE;
	
		foreach($query as $q)
		{
			$_query = DB::query(Database::UPDATE, $q)->execute();
		}

		if($success) 
		{
			$this->update_size();
			$this->add_to_index($id);
		} 
		else 
		{
			$record->destroy_document($doc);
			$this->remove_empty_documents(array($doc->id));
			$doc->id = 0;
		}
		
		$this->clear_cache();
		
		return $doc;
	}
	
	/**
	 * 
	 * @param Datasource_Document $doc
	 * @return boolean
	 */
	public function update_document($doc) 
	{
		$old = $this->get_document($doc->id);
	
		if($old !== NULL AND !$old->id)
		{
			return FALSE;
		}

		$record = $this->get_record();
		$record->document_changed($old, $doc);
		$query = $record->get_sql($doc, TRUE);

		$result = TRUE;
		foreach($query as $q)
		{
			$result = DB::query(NULL, $q)->execute() AND $result;
		}

		if($old->published != $doc->published) 
		{
			if($doc->published)
			{
				$this->add_to_index($old->id);
			}
			else
			{
				$this->remove_from_index($old->id);
			}
		} 
		elseif($old->published)
		{
			$this->update_index($old->id);
		}
		
		$this->clear_cache();

		return $result;
	}
	
	/**
	 * 
	 * @param integer $id
	 * @return \DataSource_Data_Hybrid_Document
	 */
	public function get_document($id)
	{
		$doc = NULL;

		if($id > 0) 
		{
			$doc = new DataSource_Data_Hybrid_Document($this->get_record());
			
			if(!$this->read_sql) 
			{
				$record = $this->get_record();
				$parents = $this->get_parents();
				
				$query = DB::select(array('dshybrid.id', 'id'))
					->select('ds_id', 'published', 'header')
					->from('dshybrid')
					->where('dshybrid.id', '=', $id)
					->limit(1);

				$query->select_array(  array_keys( $record->fields ));

				foreach($parents as $parent) 
				{
					$query
						->from("dshybrid_$parent")
						->where("dshybrid_$parent.id", '=', DB::expr('`dshybrid`.`id`'));
				}
				
				$this->read_sql = (string) $query;
			}
			
			$result = DB::query( Database::SELECT, $this->read_sql )
				->execute()
				->current();

			if($result)
			{
				$doc->read_values($result);
			}
		}

		return $doc;
	}
	
	/**
	 * 
	 * @return \DataSource_Data_Hybrid_Document
	 */
	public function get_empty_document() 
	{
		$record = $this->get_record();
		$doc = new DataSource_Data_Hybrid_Document($record);
		
		return $doc;
	}
	
	/**
	 * @return DataSource_Data_Hybrid_Record
	 */
	public function get_record($id = NULL, $alias = false) 
	{
		if($this->record === NULL)
		{
			$this->record = new DataSource_Data_Hybrid_Record($this);
		}

		return $this->record;
	}
	
	/**
	 * 
	 * @param string $header
	 * @return null|integer
	 */
	public function create_empty_document($header) 
	{
		$data = array(
			'ds_id' => $this->ds_id,
			'header' => $header,
			'created_on' => date('Y-m-d H:i:s'),
		);
		
		$query = DB::insert('dshybrid')
			->columns(array_keys($data))
			->values(array_values($data))
			->execute();

		$id = $query[0];

		$parents = $this->get_parents();

		$success = TRUE;

		foreach($parents as $parent) 
		{
			$query = DB::insert("dshybrid_$parent")
				->columns(array('id'))
				->values(array($id))
				->execute();
			$success = $success AND ($query[1] > 0);
		}

		if($success AND $id)
		{
			return $id;
		}
		
		$this->remove_empty_documents(array($id));

		return NULL;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return \DataSource_Data_Hybrid_Section
	 */
	public function remove_empty_documents($ids) 
	{
		if(empty($ids))
		{
			return $this;
		}

		DB::delete("dshybrid")
			->where('id', 'in', $ids)
			->execute();

		$parents = $this->get_parents();

		foreach($parents as $parent)
		{
			DB::delete("dshybrid_$parent")
				->where('id', 'in', $ids)
				->execute();
		}

		$this->remove_from_index($ids);
		
		$this->clear_cache();
		
		return $this;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return \DataSource_Data_Hybrid_Section
	 */
	public function delete($ids) 
	{
		$dsf = new DataSource_Data_Hybrid_Factory();
		$dsf->remove_documents($ids);
		
		return $this;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function get_parents()
	{
		$parents = explode(',', $this->path);
		unset($parents[0]);
		
		return $parents;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return \DataSource_Data_Hybrid_Section
	 */
	public function remove_own_documents($ids) 
	{
		$record = $this->get_record();
		$this->remove_empty_documents($ids);
		$this->update_size();
		
		return $this;
	}

	/**
	 * 
	 * @param integer $ds_id
	 * @return array
	 */
	public function get_headline( $ds_id, array $ids = NULL, $search_word = NULL )
	{
		$ds_id = (int) $ds_id;
		
		$agent = DataSource_Data_Hybrid_Agent::instance($ds_id, $ds_id, FALSE);
		
		$fields = array();
		$ds_fields = DataSource_Data_Hybrid_Field_Factory::get_related_fields($this->ds_id);

		foreach($ds_fields as $key => $field)
		{
			if( array_key_exists( $field->name, $this->fields() ))
				$fields[] = $field->id;
		}
		
		$query = $agent
			->get_query_props($fields, array(), (array) $this->doc_order)
			->select(array('d.created_on', 'date'));
		
		$query->join(array('datasources', 'dss'))
				->on('d.ds_id', '=', 'dss.ds_id')
				->select('dss.name');
		
		if( ! empty($ids) ) 
		{
			$query->where('d.id', 'in', $ids);
		}
		
		if( ! empty($search_word) ) 
		{
			$query
				->where_open()
				->where('d.id', 'like', '%'.$search_word.'%')
				->or_where('d.header', 'like', '%'.$search_word.'%')
				->where_close();
		}
		
		$result = array(0, array());

		$query = $query->execute();

		if($query->count() > 0)
		{
			$result[0] = $query->count();
			
			foreach ( $query as $row )
			{
				$hl[$row['id']] = array(
					'id' => $row['id'],
					'published' => (bool) $row['published'],
					'header' => $row['header'],
					'date' => Date::format($row['date'])
				);
				
				foreach($ds_fields as $field)
				{
					$_field = &$hl[$row['id']][$field->name];
					if(isset($row[$field->id]))
					{
						switch ($field->family)
						{
							case DataSource_Data_Hybrid_Field::TYPE_FILE:
								if($field->is_image(PUBLICPATH . $row[$field->id]))
								{
									$_field = HTML::anchor(PUBLIC_URL . $row[$field->id], __('File'), array('class' => 'popup fancybox'));
								}
								else if(!empty($row[$field->id]))
								{
									$_field = HTML::anchor(PUBLIC_URL . $row[$field->id], __('File'), array('target' => 'blank'));
								}
								else
								{
									$_field = $row[$field->id];
								}
								break;
							case DataSource_Data_Hybrid_Field::TYPE_PRIMITIVE:
								switch ($field->type)
								{
									case DataSource_Data_Hybrid_Field_Primitive::PRIMITIVE_TYPE_BOOLEAN:
										$_field = $row[$field->id] == 1 ? __('TRUE') : __('FALSE');
										break;
									case DataSource_Data_Hybrid_Field_Primitive::PRIMITIVE_TYPE_TEXT:
									case DataSource_Data_Hybrid_Field_Primitive::PRIMITIVE_TYPE_HTML:
										$_field = substr(strip_tags($row[$field->id]), 0, 500) . ' ...';
										break;
									default:
										$_field = $row[$field->id];
								}
								break;
							case DataSource_Data_Hybrid_Field::TYPE_ARRAY:
								if(!empty($row[$field->id]))
								{
									$docs = explode(',', $row[$field->id]);
									foreach($docs as $i => $id)
									{
										$docs[$i] = HTML::anchor(Route::url('datasources', array(
											'controller' => 'document',
											'directory' => 'hybrid',
											'action' => 'view'
										)) . URL::query(array(
											'ds_id' => $ds_id, 'id' => $id
										)), $id, array('target' => 'blank'));
									}
									$_field = implode(', ', $docs);
								}
								else
								{
									$hl[$row['id']][$field->name] = $row[$field->id];
								}
								break;
							default:
								$_field = $row[$field->id];
						}
						
					}
				}

				if($this->auto_cast AND $this->all_doc) 
				{
					$hl[$row['id']]['ds_id'] = $row['ds_id'];
					$hl[$row['id']]['type'] = $row['name'];
				}
			}
			
			$result[1] = $hl;
		}
		
		return $result;
	}
	
	/**
	 * 
	 * @param \DataSource_Document $doc
	 * @param string $field
	 * @param integer $id
	 * @return boolean
	 */
	function set_field($doc, $field, $id) 
	{
		$db_field = DB::select('id', 'ds_id', 'name', 'family', 'isown')
			->from('dshfields')
			->where('id', '=', $field)
			->where('from_ds', '=', $this->ds_id)
			->limit(1)
			->execute()
			->current();
		
		if($db_field === NULL)
		{
			return FALSE;
		}

		$ds_id = (int) $db_field['ds_id'];
		$field_name = $db_field['name'];
		$family = $r['family'];
		
		$doc_filed = DB::select($field_name)
			->from('dshybrid_' . $ds_id)
			->where('id', '=', $doc)
			->limit(1)
			->execute()
			->get($field_name);

		if($doc_filed === NULL)
		{
			return FALSE;
		}

		$oldvalue = $doc_filed;
		$newvalue = ($oldvalue ? $oldvalue . ',' : '') . $id;
		if(UTF8::strlen($newvalue) > 255)
		{
			return FALSE;
		}
		
		DB::update('dshybrid_' . $ds_id)
			->set(array(
				$field_name => $newvalue
			))
			->where('id', '=', $doc)
			->limit(1)
			->execute();
		
		$this->clear_cache();

		return TRUE;
	}
	
	/**
	 * @param integer $doc_id
	 * @return \DataSource_Document
	 * @throws Kohana_Exception
	 */
	public function get_doc($doc_id) 
	{
		static $ds, $doc;
		$result = NULL;
		
		$doc_id = (int) $doc_id;

		if(isset($doc[$doc_id]))
		{
			$result = $doc[$doc_id];
		}
		else 
		{
			$ds_id = DB::select('ds_id')
				->from('dshybrid')
				->where('id', '=', $doc_id)
				->execute()
				->get('ds_id');
	
			if(!isset($ds[$ds_id])) 
			{
				$ds[$ds_id] = Datasource_Data_Manager::load($ds_id);

				if($ds[$ds_id] === NULL)
				{
					throw new Kohana_Exception('NULL object');
				}
			}

			$doc[$doc_id] = $ds[$ds_id]->get_document($doc_id);
			$result = $doc[$doc_id];
		}

		return $result;
	}
	
	public function clear_cache( )
	{
		Datasource_Data_Manager::clear_cache( $this->ds_id, DataSource_Data_Hybrid_Factory::$widget_types);
	}

	public function __sleep()
	{
		$vars = array_keys(get_object_vars($this));
		unset($vars['docs'], $vars['is_indexable'], $vars['record'], $vars['read_sql'], $vars['indexed_doc_query']);

		return $vars;
	}
	
	public function __wakeup()
	{
		$this->record = NULL;
		$this->read_sql = NULL;
		$this->indexed_doc_query = NULL;
		
		parent::__wakeup();
	}
}