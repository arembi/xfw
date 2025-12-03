<header>
	<h1>
	<?php $this->print([
		'en'=>'Create HTML content',
		'hu'=>'HTML tartalom létrehozása'
	]); ?>
	</h1>
</header>
<form method="POST" action="<?php $this->print($action) ?>">
	<table>
		<tbody>
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
				<td colspan="2">
					<input type="submit" value="<?php $this->print(['en'=>'Create', 'hu'=>'Létrehoz']); ?>"/>
				</td>
			</tr>
		</tbody>
	</table>
	<?php $this->print($fields['handlerModule']->tag()) ?>
	<?php $this->print($fields['handlerMethod']->tag()) ?>
</form>
