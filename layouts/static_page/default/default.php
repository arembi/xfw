<div class="staticPage">
	<header>
		<h1><?php $this->print($title); ?></h1>
		<div><?php $this->print(['en'=> 'Created at:']); ?> <time><?php echo $createdAt ?></time></div>
		<div><?php $this->print(['en'=> 'Last updated:']); ?> <time><?php echo $updatedAt ?></time></div>
	</header>
	<section>
		<?php echo $content ?>
	</section>
</div>
