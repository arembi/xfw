<!DOCTYPE html>
<html lang="<?php $this->print($lang); ?>">
<head>
    <?php $this->embed('head');?>
</head>
<body>
    <?php $this->embed($primaryModule, $primaryModuleParams) ?>
</body>
</html>