<!DOCTYPE html>
<html lang="<?php $this->print($lang);?>"><head>
  <?php $this->embed('head');?>
</head><body>
  <main>
    <?php $this->embed($primaryModule, $primaryModuleParameters)?>
  </main>
</body></html>
