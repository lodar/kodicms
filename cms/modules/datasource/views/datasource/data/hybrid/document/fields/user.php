<?php 
	if(empty($doc->id)) $value = AuthUser::getId();
?>

<div class="control-group">
	<label class="control-label" for="<?php echo $field->name; ?>"><?php echo $field->header; ?></label>
	<div class="controls">
		<?php if($field->only_current): ?>
		<?php echo Form::hidden( $field->name, $value); ?>
		<?php echo Form::select( '', $field->get_users(), $value, array('disabled')); ?>
		<?php else: ?>
		<?php echo Form::select( $field->name, $field->get_users(), $value); ?>
		<?php endif; ?>
		
		<?php if($field->is_exists($value)): ?>
		<?php echo HTML::anchor(Route::url('backend', array('controller' => 'users', 'action' => 'edit', 'id' => $value)), $field->get_user($value)->username, array('class' => 'popup fancybox.iframe')) ; ?>
		<?php endif; ?>
	</div>
</div>