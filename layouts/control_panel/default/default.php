<header>
  <span><?php $this->embed('user_status', ['layout'=>'with_id']); ?></span>
</header>
<div class="pure-menu pure-menu-horizontal">
  <?php foreach ($cpMenuItems as $item):
    $item->processLayout()->render();
  endforeach; ?>
</div>
<main>
  <?php $this->print($main) ?>
</main>
