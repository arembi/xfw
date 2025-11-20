<header>
	<h1>
	<?php $this->print([
		'en'=>'Edit HTML content',
		'hu'=>'HTML tartalom szerkesztése'
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
			<?php foreach ($availableLanguages as $lang): ?>
			<tr>
				<td>
					<label for="title-<?php echo $lang[0]?>"><?php $this->print($fields['title-' . $lang[0]]->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['title-' . $lang[0]]->tag()) ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php foreach ($availableLanguages as $lang): ?>
			<tr>
				<td>
					<label for="content-<?php echo $lang[0]?>"><?php $this->print($fields['content-' . $lang[0]]->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['content-' . $lang[0]]->tag()) ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<td>
					<label for="createdAt"><?php $this->print($fields['createdAt']->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['createdAt']->tag()) ?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
				<input type="submit" value="<?php $this->print(['en'=>'Update', 'hu'=>'Módosít'])?>"/>
				</td>
			</tr>
		</tbody>
	</table>
	<?php $this->print($fields['handlerModule']->tag()) ?>
	<?php $this->print($fields['handlerMethod']->tag()) ?>
</form>
