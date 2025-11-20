<header>
	<h1>
	<?php $this->print([
		'en'=>'Are you sure you want to delete this HTML content?',
		'hu'=>'Biztosan törli ezt a HTML tartalmat?'
	]); ?>
	</h1>
</header>
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
				<input type="submit" value="<?php $this->print(['en'=>'Delete', 'hu'=>'Törlés']); ?>"/>
			</td>
			</tr>
		</tbody>
	</table>
	<?php $this->print($fields['handlerModule']->tag()) ?>
	<?php $this->print($fields['handlerMethod']->tag()) ?>
</form>
