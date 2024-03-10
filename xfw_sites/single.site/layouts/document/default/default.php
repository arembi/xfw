<!DOCTYPE html>
<html lang="<?php echo $lang;?>"><head>
  <?php $this->embed('head');?>
</head><body>
  <header>
    <?php $this->embed('user_status') ?>
  </header>
  <main>
    <?php $this->embed($primaryModule)?>
  </main>
  <footer>
    &copy; Xfw
  </footer>
</body></html>
