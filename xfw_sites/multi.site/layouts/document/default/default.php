<!DOCTYPE html>
<html lang="<?php echo $lang;?>"><head>
  <?php $this->embed('head');?>
</head><body>
  <?php $this->embed('user_status') ?>
  <main>
    <?php $this->embed($primaryModule)?>
  </main>
</body></html>
