<div class="pure-menu pure-menu-horizontal">
  <?php foreach ($cpMenuItems as $item):
    $item->processLayout()->render();
  endforeach; ?>
</div>