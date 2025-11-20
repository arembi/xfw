<?php if ($displayTitle): ?>
	<div class="menuTitle">
		<?php $this->print($title) ?>
	</div>
<?php endif; ?>
<nav>
	<ul class="menu level-<?php $this->print($level); ?>">
	<?php foreach ($items as $no => $item): ?>
		<li class="item-<?php echo $no;?>"><?php $this->print($item); ?></li>
	<?php endforeach; ?>
	</ul>
</nav>