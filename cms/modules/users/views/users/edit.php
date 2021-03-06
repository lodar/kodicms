<script type="text/javascript">
	var USER_ID = <?php echo (int) $user->id; ?>;
</script>
	
<?php echo Form::open(Route::url('backend', array('controller' => 'users', 'action' => $action, 'id' => $user->id)), array(
	'class' => Bootstrap_Form::HORIZONTAL
)); ?>
	<?php echo Form::hidden('token', Security::token()); ?>
	<div class="widget">
		<div class="tabbable tabs-left">
			<ul class="nav nav-tabs"></ul>
			<div class="tab-content"></div>
		</div>
		<div class="widget-header">
			<h3><?php echo __('General information'); ?></h3>
		</div>
		<div class="widget-content">
			<div class="row-fluid">
				<div class="span8">
				<?php 
					echo Bootstrap_Form_Element_Control_Group::factory(array(
						'element' => Bootstrap_Form_Element_Input::factory(array(
							'name' => 'user[name]', 'value' => $user->profile->name
						), array('id' => 'userEditNameField'))
						->label(__('Name'))
					));
					
					echo Bootstrap_Form_Element_Control_Group::factory(array(
						'element' => Bootstrap_Form_Element_Input::factory(array(
							'name' => 'user[email]', 'value' => $user->email
						), array('id' => 'userEditEmailField'))
						->label(__('E-mail'))
						->append(Bootstrap_Form_Element_Input::add_on(UI::icon('envelope')))
						->help_text(__('Use a valid e-mail address.'))
					));
					
					echo Bootstrap_Form_Element_Control_Group::factory(array(
						'element' => Bootstrap_Form_Element_Input::factory(array(
							'name' => 'user[username]', 'value' => $user->username
						), array('id' => 'userEditUsernameField'))
						->label(__('Username'))
						->append(Bootstrap_Form_Element_Input::add_on(UI::icon('user')))
						->help_text(__('At least :num characters. Must be unique.', array(
							':num' => 3
						)))
					)); 
				?>
				</div>
				<div class="span4 align-right">
					<?php if($user->id !== NULL): ?>
					<div id="UserGravatar">
						<?php echo HTML::anchor('http://gravatar.com/emails/', $user->gravatar(150, NULL, array(
							'class' => 'img-polaroid')), array(
							'target' => '_blank'
						)); ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
			
			<hr />
			
			<?php echo Bootstrap_Form_Element_Control_Group::factory(array(
				'element' => Bootstrap_Form_Element_Checkbox::factory(array(
					'name' => 'user[notice]', 'value' => 1
				))
				->checked($user->profile->notice == 1)
				->label(__('Subscribe to email notifications'))
			)); ?>
			
			<?php echo Bootstrap_Form_Element_Control_Group::factory(array(
				'element' => Bootstrap_Form_Element_Select::factory(array(
					'name' => 'user[locale]', 'options' => I18n::available_langs()
				))
				->selected($user->profile->locale)
				->label(__('Interface language'))
			)); ?>
		</div>

		<?php if( ACL::check('users.change_password') OR $user->id == AuthUser::getId() ): ?>
		<div class="widget-header spoiler-toggle" data-spoiler=".password-spoiler">
			<h3><?php echo __('Password'); ?></h3>
		</div>
		<div class="widget-content spoiler password-spoiler">
		<?php
			echo Bootstrap_Form_Element_Control_Group::factory(array(
				'element' => Bootstrap_Form_Element_Password::factory(array(
					'name' => 'user[password]'
				), array('id' => 'userEditPasswordField', 'autocomplete' => 'off', 
					'placeholder' => __('Password')))
				->label(__('Password'))
				->append(Bootstrap_Form_Element_Input::add_on(UI::icon('lock')))
				->help_text(__('At least :num characters.', array(
					':num' => Kohana::$config->load('auth')->get( 'password_length' )
				)))
			));
			
			echo Bootstrap_Form_Element_Control_Group::factory(array(
				'element' => Bootstrap_Form_Element_Password::factory(array(
					'name' => 'user[password_confirm]'
				), array('id' => 'userEditPasswordConfirmField', 
					'autocomplete' => 'off', 'placeholder' => __('Confirm Password')))
				->label(__('Confirm Password'))
			)); 
		?>
			<?php if($action == 'edit'): ?>
			<div class="alert alert-info">
				<?php echo __('Leave password blank for it to remain unchanged.'); ?>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if (Acl::check( 'users.change_roles') AND ($user->id === NULL OR $user->id > 1)): ?>
		<div class="widget-header">
			<h3><?php echo __('Roles'); ?></h3>
		</div>
		<div class="widget-content">
			<div class="row-fluid">
			<?php 
				echo Form::hidden('user_permission', (int) $user->id, array(
					'class' => 'span12'
				));
				
				echo Bootstrap_Form_Helper_Help::factory(array(
					'text' => __('Roles restrict user privileges and turn parts of the administrative interface on or off.')
				)); 
			?>
			</div>
		</div>
		<?php endif; ?>
		
		<?php if ( !empty($permissions) ): ?>
		<div class="widget-header widget-section">
			<h2><?php echo __('Section permissions'); ?></h2>
		</div>
		
		<?php foreach($permissions as $title => $actions): ?>
		<div class="widget-content widget-nopad spoiler permissions-spoiler">
			<div class="widget-header spoiler-toggle" data-spoiler=".permissions-spoiler">
				<h3><?php echo __(ucfirst($title)); ?></h3>
			</div>
			<table class='table' id="permissions-list">
				<tbody>
					<tr>
						<th>
						<?php foreach($actions as $action => $title): ?>
						<?php echo UI::label(__($title)); ?>
						<?php endforeach; ?>
						</th>
					</tr>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>
		<?php endif; ?>
		
		<?php Observer::notify('view_user_edit_plugins', $user); ?>
		
		<div class="form-actions widget-footer">
			<?php echo UI::actions($page_name); ?>
		</div>
	
	</div>
<?php Form::close(); ?>