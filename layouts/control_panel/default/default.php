<header>
  <span>Kijelentkezés</span>
  <span><?php $this->embed('user_status', ['layout'=>'with_id']); ?></span>
</header>
<aside>
  <?php foreach ($cpMenuItems as $item):
    $item->render();
  endforeach; ?>
</aside>
<main>
  <?php $this->print($main) ?>
</main>
