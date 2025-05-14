  <form method="POST" action="<?php $this->print($action)?>" enctype="<?php $this->print($enctype)?>">
    <?php $this->print($fields['formId']->tag()) ?>
  	<input type="submit" value="Kilépés"/>
  </form>
