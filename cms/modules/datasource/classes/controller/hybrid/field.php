<?php defined( 'SYSPATH' ) or die( 'No direct access allowed.' );

class Controller_Hybrid_Field extends Controller_System_Datasource
{
	
	public function action_edit()
	{
		$id = (int) $this->request->param('id');
		$field = DataSource_Data_Hybrid_Field_Factory::get_field($id);
		
		if($field === NULL)
		{
			throw new HTTP_Exception_404('Field ID :id not found', array(':id' => $id));
		}
		
		$ds = Datasource_Data_Manager::load($field->ds_id);
		
		if($this->request->method() === Request::POST)
		{
			return $this->_edit($field);
		}
		
		$this->breadcrumbs
			->add($ds->name, 'hybrid/section/edit/' . $ds->ds_id)
			->add(__(':action field', array(':action' => __(ucfirst($this->request->action())))));

		$type = $field->family == DataSource_Data_Hybrid_Field::TYPE_PRIMITIVE ? $field->type : $field->family;
		
		$this->template->content = View::factory('datasource/data/hybrid/field/edit', array(
			'ds' => $ds,
			'field' => $field,
			'type' => $type,
			'sections' => $this->_get_sections(),
			'post_data' => Session::instance()->get_once('post_data', array())
		));
	}
	
	private function _edit($field)
	{
		try 
		{
			$old_field = clone($field);
			$field->set($this->request->post());
			DataSource_Data_Hybrid_Field_Factory::update_field($old_field, $field);
		}
		catch (Validation_Exception $e)
		{
			Session::instance()->set('post_data', $this->request->post());
			Messages::errors($e->errors('validation'));
			$this->go_back();
		}
		
		// save and quit or save and continue editing?
		if ( $this->request->post('commit') !== NULL )
		{
			$this->go( URL::site('hybrid/section/edit/' . $field->ds_id, FALSE));
		}
		else
		{
			$this->go_back();
		}
	}

	public function action_add( )
	{
		$ds_id = (int) $this->request->param('id');

		$ds = Datasource_Data_Manager::load($ds_id);
		
		if($this->request->method() === Request::POST)
		{
			return $this->_add($ds);
		}
		$this->breadcrumbs
			->add($ds->name, 'hybrid/section/edit/' . $ds->ds_id)
			->add(__(':action field', array(':action' => ucfirst($this->request->action()))));
		
		$this->template->content = View::factory('datasource/data/hybrid/field/add', array(
			'ds' => $ds,
			'sections' => $this->_get_sections(),
			'post_data' => Session::instance()->get_once('post_data', array())
		));
	}
	
	private function _add($ds)
	{
		try 
		{
			$data = $this->request->post();
			
			$family = $data['family'];
			unset($data['family']);
			
			$field = DataSource_Data_Hybrid_Field::factory($family, $data);
			$field_id = DataSource_Data_Hybrid_Field_Factory::create_field($ds->get_record(), $field);
		}
		catch (Validation_Exception $e)
		{
			Session::instance()->set('post_data', $this->request->post());
			Messages::errors($e->errors('validation'));
			$this->go_back();
		}
		
		if(!$field_id)
		{
			Messages::errors(__('Error. Field not created'));
			$this->go('hybrid/section/edit/' . $ds->ds_id);
		}
		
		Session::instance()->delete('post_data');
		
		$this->go('hybrid/field/edit/' . $field_id);
		
	}

	public function action_remove( )
	{
		$ds_id = (int) $this->request->param('id');
		
		$fields = $this->request->post('field');
		
		$ds = Datasource_Data_Manager::load($ds_id);
		DataSource_Data_Hybrid_Field_Factory::remove_fields($ds->get_record(), $fields);
		
		$this->json = array(
			'status' => TRUE,
			'fields' => $fields
		);
	}
	
	protected function _get_sections()
	{
		$map = Datasource_Data_Manager::get_tree();
		$hds = Datasource_Data_Manager::get_all(Datasource_Data_Manager::DS_HYBRID);
		
		$sections = array(); 
		
		foreach ( Datasource_Data_Manager::types() as $key => $value )
		{
			if($key != Datasource_Data_Manager::DS_HYBRID)
			{
				foreach ( $map[$key] as $id => $name )
				{
					$sections[$key][$id] = $name;
				}
			}
			else
			{
				foreach ( $hds as $id => $data )
				{
					$sections[$key][$id] = $data['name'];
				}
			}
		}
		
		return $sections;
	}
}