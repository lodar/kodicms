<?php defined('SYSPATH') OR die('No direct access allowed.');

$route = (IS_BACKEND ? '('.ADMIN_DIR_NAME.'/)' : '') . '<directory>/<controller>/<action>';
$actions = array(
	'identify',
	'login', 'complete_login',
	'register', 'complete_register',
	'connect', 'complete_connect',
	'disconnect', 'complete_disconnect'
);

Route::set('accounts-auth', $route, array(
	'directory' => '(openid|oauth)', 
	'action' => '('.implode('|', $actions).')'
));

Observer::observe('view_user_edit_plugins', function($user) {

	echo View::factory('accounts/userblock/edit', array(
		'user' => $user,
		'settings_link' => Route::url('backend', array(
			'controller' => 'setting')
		) . '#social-accounts-settings',
		'params' => Config::get('oauth.accounts'),
		'socials' => $user->socials->find_all(),
		'providers' => SSO::connected_accounts(),
		'linked' => array()
	));
});

Observer::observe('admin_login_form_after_button', function() {
	echo View::factory('accounts/userblock/login', array(
		'providers' => SSO::connected_accounts(),
		'params' => Config::get('social')
	));
});

if(IS_BACKEND)
{
	Observer::observe('view_setting_plugins', function() {
		echo View::factory('accounts/settings', array(
			'oauth' => Config::get('oauth.accounts'),
			'params' => Config::get('social')
		));
	});

	Observer::observe('save_settings', function($post) {
		if(!isset($post['setting']['oauth']['register'])) 
		{
			Config::set('oauth', 'register', 0);
		}
	});
}