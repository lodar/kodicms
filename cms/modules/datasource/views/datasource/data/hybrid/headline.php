<table class="table table-striped">
	<colgroup>
		<?php if(Acl::check('hybrid'.$ds_id.'.document.edit')): ?>
		<col width="30px" />
		<?php endif; ?>
		
		<?php foreach ($fields as $key => $field): ?>
		<col <?php if(Arr::get($field, 'width') !== NULL) echo 'width="'.$field['width'].'"px'; ?>/>
		<?php endforeach; ?>
	</colgroup>
	<thead>
		<tr>
			<?php if(Acl::check('hybrid'.$ds_id.'.document.edit')): ?>
			<th class="row-checkbox" id="cb-all"><?php echo Form::checkbox('doc[]'); ?></th>
			<?php endif; ?>
			
			<?php foreach ($fields as $key => $field): ?>
			<th><?php echo __(Arr::get($field, 'name')); ?></th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($data[1] as $id => $row): ?>
		<tr data-id="<?php echo $id; ?>" class="<?php echo !$row['published'] ? 'unpublished' : ''; ?>">
			<?php if(Acl::check('hybrid'.$ds_id.'.document.edit')): ?>
			<td class="row-checkbox"><?php echo Form::checkbox('doc[]', $id); ?></td>
			<?php endif; ?>

			<?php foreach ($fields as $key => $field): ?>
			<?php if(isset($row[$key])): ?>
				<?php if(Arr::get($field, 'type') == 'link'): ?>
					<?php if(Acl::check('hybrid'.$ds_id.'.document.view') OR Acl::check('hybrid'.$ds_id.'.document.edit')): ?>
					<th class="row-<?php echo $key; ?>"><?php echo HTML::anchor(Route::url('datasources', array(
							'controller' => 'document',
							'directory' => 'hybrid',
							'action' => 'view'
						)) . URL::query(array(
							'ds_id' => $ds_id, 'id' => $id
						)), $row[$key]); ?></th>
					<?php else: ?>
					<td class="row-<?php echo $key; ?>"><strong><?php echo $row[$key]; ?></strong></td>
					<?php endif; ?>

				<?php else: ?>
				<td class="row-<?php echo $key; ?>"><?php echo $row[$key]; ?></td>
				<?php endif; ?>
			<?php endif; ?>
			<?php endforeach; ?>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
</div>
<div class="widget-header">
	<?php echo __('Total doucments: :num', array(
		':num' => $data[0]
	)); ?>