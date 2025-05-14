<h2>This menu uses the default_2 layout</h2>
<?php if ($displayTitle): ?>
  <div class="menuTitle">
    <?php $this->print($title) ?>
  </div>
<?php endif; ?>

<ul class="menu level-<?php $this->print($level);?>">
  <?php foreach ($menuItems as $no => $item) :?>
    <li class="item-<?php echo $no;?>"><?php $this->print($item);?></li>
  <?php endforeach;?>
</ul>
