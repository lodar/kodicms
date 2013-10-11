<?php defined('SYSPATH') or die('No direct access allowed.');

class Controller_Backup extends Controller_System_Backend {
	
	public function action_index() 
	{
		$files = array();
		$handle = opendir(BACKUP_PLUGIN_FOLDER);
		while (false !== ($file = readdir($handle))) 
		{
			if (!preg_match("/(sql|zip)/", $file, $m)) 
			{
				continue;
			}
			
			$date_create = preg_replace('![^\d]*!', '', $file);
			$date_create = preg_replace('#^([\d]{4})([\d]{2})([\d]{2})([\d]{2})([\d]{2})([\d]{2})$#', '$3/$2/$1 $4:$5:$6', $date_create);

			$files[$file] = array(
				'size' => Text::bytes(filesize(BACKUP_PLUGIN_FOLDER.$file)),
				'path' => BACKUP_PLUGIN_FOLDER.$file,
				'date' => $date_create
			);
		}

		closedir($handle);
		
		$this->template->title = __('Backup');
		
		$this->template->content = View::factory('backup/index', array(
			'files' => $files
		));
	}
	
	public function action_view()
	{
		$file = $this->request->param('file');
		$backup = Model_Backup::factory(BACKUP_PLUGIN_FOLDER . $file);
		$this->template->title = __( 'Backup view :file', array(':file' => $file));
		
		$this->template->content = View::factory('backup/view', array(
			'model' => $backup,
			'filename' => $file
		));
	}
	
	public function action_database()
	{
		$this->auto_render = FALSE;

		$backup = Model_Backup::factory(BACKUP_PLUGIN_FOLDER . 'db-'.date('YmdHis').'.sql')
			->create()
			->save();
		
		Kohana::$log->add(Log::INFO, 'Create database backup')->write();
		
		Messages::success(__('Database backup created succefully'));
		
		$this->go_back();
	}
	
	public function action_filesystem()
	{
		if($backup = Model_Backup::factory(BACKUP_PLUGIN_FOLDER . 'filesystem-'.date('YmdHis').'.zip')
			->create())
		{
			Kohana::$log->add(Log::INFO, 'Create filesystem backup')->write();
			Messages::success(__('Filesystem backup created succefully'));
		}
		
		$this->go_back();
	}

	public function action_restore()
	{
		$this->auto_render = FALSE;

		$file = $this->request->param('file');

		$backup = Model_Backup::factory(BACKUP_PLUGIN_FOLDER . $file)
			->restore();
		
		Kohana::$log->add(Log::INFO, 'Restore backup')->write();
		Messages::success(__('Backup restored succefully'));
		
		$this->go_back();
	}
	
	public function action_delete()
	{
		$this->auto_render = FALSE;

		$file = $this->request->param('file');

		if(!file_exists(BACKUP_PLUGIN_FOLDER.$file))
		{
			throw new HTTP_Exception_404('File :file not exist', array(':file' => $file));
		}
		
		unlink(BACKUP_PLUGIN_FOLDER.$file);
		
		Kohana::$log->add(Log::ALERT, 'Delete backup file')->write();
		Messages::success(__('File :filename deleted succefully', array(
			':filename' => $file
		)));
		$this->go_back();
	}
	
	public function action_upload()
	{
		$this->auto_render = FALSE;

		$errors = array();

		# Проверяем файл
		if(!isset($_FILES['file']))
		{
			$this->go_back();
		}
		
		$file = $_FILES['file'];
		
		if (!is_dir(BACKUP_PLUGIN_FOLDER))
		{
			$errors[] = __('Folder (:folder) not exist!', array(
				':folder' => BACKUP_PLUGIN_FOLDER
			));
		}
		
		if(!is_writable(BACKUP_PLUGIN_FOLDER))
		{
			$errors[] = __('Folder (:folder) must be writable!', array(
				':folder' => BACKUP_PLUGIN_FOLDER
			));
		}

		# Проверяем на пустоту
		if(!Upload::not_empty($file))
		{
			$errors[] = __('File is not attached!');
		}

		# Проверяем на расширение
		if(!Upload::type($file, array('sql', 'zip')))
		{
			$errors[] = __('Bad format of file!');
		}
		
		if(!empty($errors))
		{
			Messages::errors($errors);
			$this->go_back();
		}
		
		$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

		# Имя файла
		$filename = 'uploaded-' . date('YmdHis') . '-' . $file['name'];

		Upload::$default_directory = BACKUP_PLUGIN_FOLDER;
			
		# Cохраняем оригинал и продолжаем работать, если ок: 
		if ($file = Upload::save($file, $filename, NULL, 0777))
		{
			Messages::success(__('File :filename uploaded succefully', array(
				':filename' => $filename
			)));
			
			Kohana::$log->add(Log::ALERT, 'Backup file :filename uploaded', array(
				':filename' => $filename
			))->write();

			$this->go_back();
		}
	}
}