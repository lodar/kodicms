<?php defined('SYSPATH') or die('No direct access allowed.');

if (PHP_SAPI != 'cli')
{
	define('IS_BACKEND', URL::match(ADMIN_DIR_NAME, Request::detect_uri()));
}

if( ! defined( 'IS_BACKEND' )) define('IS_BACKEND', FALSE);

// CMS defaults
define('ADMIN_URL',			BASE_URL . ADMIN_DIR_NAME . '/');
define('PLUGINS_URL',		BASE_URL . 'cms/plugins/');
define('PUBLIC_URL',		BASE_URL . 'public/');

define('PUBLICPATH',		DOCROOT . 'public' . DIRECTORY_SEPARATOR);
define('TMPPATH',			PUBLICPATH . 'temp' . DIRECTORY_SEPARATOR);
define('LAYOUTS_SYSPATH',	DOCROOT . 'layouts' . DIRECTORY_SEPARATOR);
define('SNIPPETS_SYSPATH',	DOCROOT . 'snippets' . DIRECTORY_SEPARATOR);

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set( DEFAULT_TIMEZONE );

/**
 * Set the default cookie salt
 */
Cookie::$salt = COOKIE_SALT;

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules( array(
	'users'			=> MODPATH . 'users',
	'kodicms'		=> MODPATH . 'kodicms',
	'assets'		=> MODPATH . 'assets',		// Asset Manager
	'cache'			=> MODPATH . 'cache',		// Cache manager
	'database'		=> MODPATH . 'database',	// Database access
	'auth'			=> MODPATH . 'auth',		// Basic authentication
	'orm'			=> MODPATH . 'orm',			// Object Relationship Mapping,
	'oauth'			=> MODPATH . 'oauth',
	'sso'			=> MODPATH . 'sso',
	'minion'		=> MODPATH . 'minion',		// Minion
	'pagination'	=> MODPATH . 'pagination',
	'email'			=> MODPATH . 'email',
	'email_queue'	=> MODPATH . 'email_queue',
	'filesystem'	=> MODPATH . 'filesystem',
	'image'			=> MODPATH . 'image',
	'userguide'		=> MODPATH . 'userguide',	// User guide and API documentation,
	'scheduler'		=> MODPATH . 'scheduler',
	'snippet'		=> MODPATH . 'snippet',
	'widget'		=> MODPATH . 'widget',
	'reflinks'		=> MODPATH . 'reflinks',
	'elfinder'		=> MODPATH . 'elfinder',
	'ace'			=> MODPATH . 'ace',
	'api'			=> MODPATH . 'api',
	'bootstrap'		=> MODPATH . 'bootstrap',
	'navigation'	=> MODPATH . 'navigation',
	'breadcrumbs'	=> MODPATH . 'breadcrumbs',
	'behavior'		=> MODPATH . 'behavior',
	'plugins'		=> MODPATH . 'plugins',
) );

Kohana::$config->attach(new Config_Database);

Observer::notify('modules::afer_load');

Route::set( 'admin_media', 'cms/media/<file>', array(
	'file' => '.*'
))
	->defaults( array(
		'directory' => 'system',
		'controller' => 'media',
		'action' => 'media',
	) );

Route::set( 'user', ADMIN_DIR_NAME.'/<action>(?next=<next_url>)', array(
	'action' => '(login|logout|forgot)',
) )
	->defaults( array(
		'controller' => 'login',
	) );

Route::set( 'templates', ADMIN_DIR_NAME.'/(<controller>(/<action>(/<id>)))', array(
	'controller' => '(layout|snippet)',
	'id' => '.*'
) )
	->defaults( array(
		'controller' => 'index',
		'action' => 'index',
	) );

Route::set( 'downloader', '('.ADMIN_DIR_NAME.'/)download/<path>', array(
	'path' => '.*'
) )
	->defaults( array(
		'directory' => 'system',
		'controller' => 'download',
		'action' => 'index',
	) );

Route::set( 'backend', ADMIN_DIR_NAME.'(/<controller>(/<action>(/<id>)))')
	->defaults( array(
		'controller' => Config::get('site', 'default_tab'),
		'action' => 'index',
	) );

Route::set( 'system', '<directory>-<controller>-<action>(/<id>)', array(
	'directory' => '(ajax|action|form)',
	'controller' => '[A-Za-z\_]+',
	'action' => '[A-Za-z\_]+',
	'id' => '.+',
) );

Route::set( 'default', '(<page>)(<suffix>)' , array(
	'page' => '.*',
	'suffix' => URL_SUFFIX
) )
	->defaults( array(
		'controller' => 'front',
		'action' => 'index',
		'suffix' => URL_SUFFIX
	) );