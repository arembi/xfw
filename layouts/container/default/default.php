<div>
<?php if ($displayTitle):?>
	<header>
		<h2><?php $this->print($title);?></h2>
	</header>
<?php endif;?>
	<div>
		<?php $this->print($content);?>
	</div>
</div>
