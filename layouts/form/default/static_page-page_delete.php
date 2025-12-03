<div>
	<?php $this->print([
		'en'=>'Are you sure you want to delete this static page?',
		'hu'=>'Biztosan törölni szeretné ezt az oldalt?'
	]); ?>
</div>
<form method="POST" action="<?php $this->print($action) ?>">
	<table>
		<tbody>
			<tr>
			<td>
				<label for="id"><?php $this->print($fields['id']->label()) ?></label>
			</td>
			<td>
				<?php $this->print($fields['id']->tag()) ?>
			</td>
			</tr>
			<tr>
			<td colspan="2">
				<input type="submit" value="Delete"/>
			</td>
			</tr>
		</tbody>
	</table>
	<?php $this->print($fields['handlerModule']->tag()) ?>
	<?php $this->print($fields['handlerMethod']->tag()) ?>
</form>
