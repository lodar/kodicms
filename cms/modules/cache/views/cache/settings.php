<div class="widget-header spoiler-toggle" data-spoiler=".cache-settings" data-hash="cache-settings">
	<h3 id="cache-settings"><?php echo __('Cache settings'); ?></h3>
</div>
<div class="widget-content spoiler cache-settings">
	<div class="well">
		<?php if( ACL::check('cache.clear')): ?>
		<?php echo UI::button(__('Clear cache'), array(
			'icon' => UI::icon( 'stethoscope' ),
			'id' => 'clear-cache',
			'class' => 'btn btn-warning'
		)); ?>
		<?php endif; ?>
	</div>
	<div class="control-group">
		<label class="control-label"><?php echo __('Pages cache time'); ?></label>
		<div class="controls">
			<?php echo Form::input( 'setting[cache][front_page]', (int) Config::get('cache', 'front_page'), array(
				'class' => 'input-mini'
			)); ?> <span class="muted"><?php echo __('(Sec.)'); ?></span>
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label"><?php echo __('Page parts cache time'); ?></label>
		<div class="controls">
			<?php echo Form::input( 'setting[cache][page_parts]', (int) Config::get('cache', 'page_parts'), array(
				'class' => 'input-mini'
			)); ?> <span class="muted"><?php echo __('(Sec.)'); ?></span>
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label"><?php echo __('Page tags cache time'); ?></label>
		<div class="controls">
			<?php echo Form::input( 'setting[cache][tags]', (int) Config::get('cache', 'tags'), array(
				'class' => 'input-mini'
			)); ?> <span class="muted"><?php echo __('(Sec.)'); ?></span>
		</div>
	</div>
	
	<hr />
	<?php /*
	<div class="control-group">
		<?php echo Form::label('setting_cache_driver', __('Cache driver'), array('class' => 'control-label')); ?>
		<div class="controls">
			<?php echo Form::select('setting[cache][driver]', array(
				'file' => __('File cache'), 'sqlite' => __('SQLite cache'), 'memcachetag' => __('Memcache')
			), Cache::$default, array('id' => 'setting_cache_driver'));?>
		</div>
	</div>
	 * 
	 */
	?>
</div>