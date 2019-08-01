<div id="loginForm">
  <form method="POST" action="<?php $this->print($action) ?>" autocomplete="off">
    <?php $this->print($fields['username']['label']) ?>: <?php $this->print($fields['username']['tag']) ?>
    <?php $this->print($fields['password']['label']) ?>: <?php $this->print($fields['password']['tag']) ?>
    <?php $this->print($fields['language']['label']) ?>: <?php $this->print($fields['language']['tag']) ?>
    <?php $this->print($fields['formID']['tag']) ?>
  	<input type="submit" value="Belépés"/>
  </form>
</div>
