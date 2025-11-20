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
					<label for="pageTitle-<?php echo $lang[0]?>"><?php $this->print($fields['pageTitle-' . $lang[0]]->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['pageTitle-' . $lang[0]]->tag()) ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php foreach ($availableLanguages as $lang): ?>
			<tr>
				<td>
					<label for="pageExcerpt-<?php echo $lang[0]?>"><?php $this->print($fields['pageExcerpt-' . $lang[0]]->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['pageExcerpt-' . $lang[0]]->tag()) ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php foreach ($availableLanguages as $lang): ?>
			<tr>
				<td>
					<label for="pageContent-<?php echo $lang[0]?>"><?php $this->print($fields['pageContent-' . $lang[0]]->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['pageContent-' . $lang[0]]->tag()) ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<td>
					<label for="thumbnail"><?php $this->print($fields['thumbnail']->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['thumbnail']->tag()) ?>
				</td>
			</tr>
			<tr>
				<td>
					<label for="createdBy"><?php $this->print($fields['createdBy']->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['createdBy']->tag()) ?>
				</td>
			</tr>
			<tr>
				<td>
					<label for="routeId"><?php $this->print($fields['routeId']->label()) ?></label>
				</td>
				<td>
					<?php $this->print($fields['routeId']->tag()) ?>
				</td>
			</tr>
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