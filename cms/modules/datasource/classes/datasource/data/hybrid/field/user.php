<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * @package    Kodi/Datasource
 */

class DataSource_Data_Hybrid_Field_User extends DataSource_Data_Hybrid_Field {
	
	protected $_props = array(
		'default' => NULL,
		'isreq' => FALSE,
		'only_current' => FALSE
	);

	public function __construct( array $data )
	{
		parent::__construct( $data );
		
		$this->family = self::TYPE_USER;
		$this->type = self::TYPE_USER;
	}
	
	public function get_type()
	{
		return 'TINYINT(4)';
	}
	
	public function set( array $data )
	{
		if(!isset($data['only_current']))
		{
			$data['only_current'] = FALSE;
		}
		
		return parent::set( $data );
	}
	
	public function onCreateDocument($doc) 
	{
		$doc->fields[$this->name] = AuthUser::getId();
	}
	
	public function onUpdateDocument($old, $new)
	{
		if($this->only_current === TRUE)
		{
			$new->fields[$this->name] = AuthUser::getId();
		}
		
		if( ! $this->is_exists( $new->fields[$this->name] ))
		{
			$new->fields[$this->name] = $old->fields[$this->name];
		}
	}
	
	public function get_user($id)
	{
		return ORM::factory('user', $id);
	}
	
	public function is_exists($id)
	{
		return $this->get_user($id)->loaded();
	}
	
	public function get_users()
	{
		$users = array('--------');
		$users = $users + ORM::factory('user')->find_all()->as_array('id', 'username');
		
		return $users;
	}

	public static function set_doc_field( $widget, $field, $row, $fid )
	{
		
		return !empty($row[$fid]) 
			? array(
				'username' => $row[$fid],
				'id' => $row['user_id']
			)
			: array(
				'username' => '',
				'id' => ''
			);
	}
}