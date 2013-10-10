<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_Widget_Hybrid_Document extends Model_Widget_Hybrid {
	
	/**
	 *
	 * @var array 
	 */
	public $doc_fields = array();
	
	/**
	 *
	 * @var array 
	 */
	public $doc_fetched_widgets = array();

	/**
	 *
	 * @var string 
	 */
	public $docs_uri = NULL;
	
	/**
	 *
	 * @var string 
	 */
	public $doc_id_field = 'id';

	/**
	 *
	 * @var bool 
	 */
	public $crumbs = FALSE;
		
	/**
	 *
	 * @var array
	 */
	public $document = array();

	/**
	 * 
	 * @param array $data
	 */
	public function set_values(array $data) 
	{
		$this->doc_fields = $this->doc_fetched_widgets = array();
		
		parent::set_values($data);
		
		$this->docs_uri = Arr::get($data, 'docs_uri', $this->docs_uri);
		$this->doc_id = Arr::get($data, 'doc_id', $this->doc_id);
		
		$this->throw_404 = (bool) Arr::get($data, 'throw_404');
		$this->crumbs = (bool) Arr::get($data, 'crumbs');
		
		return $this;
	}
	
	public function set_field($fields = array())
	{
		if(!is_array( $fields)) return;
		foreach($fields as $f)
		{
			if(isset($f['id']))
			{
				$this->doc_fields[] = (int) $f['id'];
			
				if(isset($f['fetcher']))
					$this->doc_fetched_widgets[(int) $f['id']] = (int) $f['fetcher'];
			}
		}
		
		return $this;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function options()
	{
		$datasources = Datasource_Data_Manager::get_all('hybrid');
		
		$options = array();
		foreach ($datasources as $value)
		{
			$options[$value['id']] = $value['name'];
		}
		
		return $options;
	}
	
	public function get_doc_ids()
	{
		$data = array('ID');
		
		$fields = DataSource_Data_Hybrid_Field_Factory::get_related_fields($this->ds_id);
		foreach ($fields as $field)
		{
			if($field->family != DataSource_Data_Hybrid_Field::TYPE_PRIMITIVE)				continue;
			
			if(
				$field->type == DataSource_Data_Hybrid_Field_Primitive::PRIMITIVE_TYPE_STRING
			||
				$field->type == DataSource_Data_Hybrid_Field_Primitive::PRIMITIVE_TYPE_INTEGER
			||
				$field->type == DataSource_Data_Hybrid_Field_Primitive::PRIMITIVE_TYPE_SLUG
			)
			{
				$data[$field->id] = $field->header;
			}
		}
		return $data;
	}

	public function on_page_load()
	{
		parent::on_page_load();
		
		$doc = $this->get_document();
		
		$page = $this->_ctx->get_page();
		$page->title = $page->meta_title = $doc['header'];
	}
	
	public function change_crumbs( Breadcrumbs &$crumbs )
	{
		parent::change_crumbs( $crumbs );
		$page = $this->_ctx->get_page();
		$doc = $this->get_document();
		
		$crumb = $crumbs->get_by('url', URL::site($page->url));
		if($crumb !== NULL)
		{
			$crumb->name = $doc['header'];
		}
	}

	public function fetch_data()
	{
		$result = array();
		$result = $this->get_document();
		
		return array(
			'doc' => $result
		);
	}
	
	public function get_document($id = NULL)
	{
		$result = array();
		
		if($id === NULL)
		{
			$id = $this->get_doc_id();
		}
		
		if(isset($this->document[$id]))
		{
			return $this->document[$id];
		}
		
		$agent = $this->get_agent();
		$query = $agent->get_query_props($this->doc_fields, $this->doc_fetched_widgets);
		
		if(isset($agent->ds_fields[$this->doc_id]))
		{
			$id_field = DataSource_Data_Hybrid_Field::PREFFIX.$agent->ds_fields[$this->doc_id]['name'];
		}
		else
		{
			$id_field = 'ds.id';
		}
		
		$result = $query->where($id_field, '=', $id)
			->where('d.published', '=', 1)
			->group_by('d.id')
			->limit(1)
			->execute()
			->current();
		
		if(empty($result) )
		{	
			if($this->throw_404)
				$this->_ctx->throw_404();
			
			return $result;
		}
		
		foreach ($result as $key => $value)
		{
			if( ! isset($agent->ds_fields[$key])) continue;

			$field = & $agent->ds_fields[$key];
			$related_widget = NULL;
				
			$field_class = 'DataSource_Data_Hybrid_Field_' . $field['type'];
			$field_class_method = 'set_doc_field';
			if( class_exists($field_class) AND method_exists( $field_class, $field_class_method ))
			{
				$result[$field['name']] = call_user_func_array($field_class.'::'.$field_class_method, array( $this, $field, $result, $key, 3));
				
				$result['_' . $field['name']] = $result[$key];

				unset($result[$key]);
				continue;
			}

			switch($field['type']) {
				case DataSource_Data_Hybrid_Field::TYPE_DATASOURCE:
					array(
						'id' => $row[$fid]
					);
					break;
				default:
					$result[$field['name']] = $row[$fid];

			}
			
			unset($result[$key]);
		}
		
		$this->document[$id] = $result;
		
		return $result;
	}
	
	public function get_doc_id()
	{
		return $this->_ctx->get('slug');
	}
	
	public function get_cache_id()
	{
		if(IS_BACKEND) return;

		return 'Widget::' 
			. $this->type . '::' 
			. $this->id . '::' 
			. $this->get_doc_id();
	}
	
	public function count_total()
	{
		return 1;
	}
	
	public function fetch_backend_content()
	{
		try
		{
			$content = View::factory( 'widgets/backend/' . $this->backend_template(), array(
					'widget' => $this
				))->set($this->backend_data());
		}
		catch( Kohana_Exception $e)
		{
			$content = NULL;
		}
		
		return $content;
	}
}