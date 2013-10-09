<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * @package    Kodi/Datasource
 */

class DataSource_Data_Hybrid_Field_Tags extends DataSource_Data_Hybrid_Field {
	
	const TABLE_NAME = 'hybrid_tags';

	protected $_props = array(
		'isreq' => FALSE
	);
	
	public function __construct( array $data )
	{
		parent::__construct( $data );
		
		$this->family = self::TYPE_TAGS;
	}
	
	public function onUpdateDocument($old, $new) 
	{
		$o = empty($old->fields[$this->name]) ? array() : explode(',', $old->fields[$this->name]);
		$n = empty($new->fields[$this->name]) ? array() : explode(',', $new->fields[$this->name]);

		$this->update_tags($o, $n, $new->id);
	}
	
	public function onRemoveDocument( $doc )
	{
		$tags = explode(',', $doc->fields[$this->name]);
		$this->update_tags($tags, array(), $doc->id);
	}
	
	public function convert_to_plain($doc) 
	{
		if(is_array($doc->fields[$this->name]))
		{
			$doc->fields[$this->name] = implode(', ', $doc->fields[$this->name]);
		}
	}
	
	public function get_type()
	{
		return 'TEXT NOT NULL';
	}
	
	public function update_tags($old, $new, $doc_id)
	{
		if(empty($new))
		{
			foreach($old as $tag)
			{
				DB::update(Model_Tag::tableName())
					->set(array('count' => DB::expr('count - 1')))
					->where('name', '=', $tag)
					->execute();
			}
			
			return DB::delete(self::TABLE_NAME)
				->where('doc_id', '=', $doc_id)
				->where('field_id', '=', $this->id)
				->execute();
		}
		
		$old_tags = array_diff($old, $new);
		$new_tags = array_diff($new, $old);

		// insert all tags in the tag table and then populate the page_tag table
		foreach( $new_tags as $index => $tag_name )
		{
			if ( empty($tag_name) )	continue;

			$tag = Record::findOneFrom('Model_Tag', array(
				'where' => array(
					array('name', '=', $tag_name)
				)
			));
			
			

			// try to get it from tag list, if not we add it to the list
			if ( !($tag instanceof Model_Tag))
			{
				$tag = new Model_Tag(array('name' => trim($tag_name)));
			}

			$tag->count++;
			$tag->save();
			
			echo debug::vars($tag);

			$data = array(
				'field_id' => $this->id,
				'doc_id' => $doc_id,
				'tag_id' => $tag->id
			);

			DB::insert(self::TABLE_NAME)
				->columns(array_keys($data))
				->values($data)
				->execute();

		}

		// remove all old tag
		foreach( $old_tags as $index => $tag_name )
		{
			// get the id of the tag
			$tag = Record::findOneFrom('Model_Tag',
					array('where' => array(array('name', '=', $tag_name))));

			DB::delete(self::TABLE_NAME)
				->where('doc_id', '=', $doc_id)
				->where('field_id', '=', $this->id)
				->where('tag_id', '=', $tag->id)
				->execute();

			$tag->count--;
			$tag->save();
		}
	}
	
	public function remove()
	{
		$ids = DB::select('tag_id')
			->from(self::TABLE_NAME)
			->where('field_id', '=', $this->id)
			->execute()
			->as_array(NULL, 'tag_id');
		
		foreach($ids as $id)
		{
			DB::update(Model_Tag::tableName())
				->set(array('count' => DB::expr('count - 1')))
				->where('id', '=', $id)
				->execute();
		}
			
		DB::delete(self::TABLE_NAME)
			->where('field_id', '=', $this->id)
			->execute();
		
		return parent::remove();
	}
	
	public function remove_tags($ids)
	{
		
	}

	/**
	 * @param Model_Widget_Hybrid
	 * @param array $field
	 * @param array $row
	 * @param string $fid
	 * @return mixed
	 */
	public static function set_doc_field( $widget, $field, $row, $fid, $recurse )
	{
		return !empty($row[$fid]) ? explode(',', $row[$fid]) : array();
	}
}