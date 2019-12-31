<!DOCTYPE html>
<html lang="<?php $this->print($lang);?>"><head>
  <?php $this->embed('head');?>
</head><body>
  <?php $this->embed('body_start');?>
  <main>
    <?php $this->embed($primaryModule)?>
  </main>
  <?php $this->embed('body_end');?>
</body></html>
