<div class="staticPage">
	<header>
		<h1><?php $this->print($title); ?></h1>
		<div><?php $this->print(['en'=> 'Created at:']); ?> <time><?php $this->print($createdAt) ?></time></div>
		<div><?php $this->print(['en'=> 'Last updated:']); ?> <time><?php $this->print($updatedAt) ?></time></div>
	</header>
	<section>
		<?php $this->print($content); ?>
	</section>
</div>
