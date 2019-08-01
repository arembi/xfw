<aside>
  <?php foreach ($cpMenuItems as $item):
    $item->render();
  endforeach; ?>
</aside>
<main>
  <?php $this->print($main) ?>
</main>
