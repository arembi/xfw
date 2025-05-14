<div class="user-status">
<?php if($user->isLoggedIn()): ?>
  <div>
    Hi, <?php $this->print($firstName . ' ' . $lastName . ' (' . $username . ')')?><br>
    Group: <?php $this->print($userGroup) ?> (CL: <?php $this->print($clearanceLevel) ?>)
  </div>
  <div>
    Log out: <?php $this->embed('form', ['formId' => 2, 'autoBuild'=>true]);?>
  </div>
<?php else: ?>
  <div>
    Hi, <?php $this->print($firstName)?><br>
  </div>
  <div>
    <?php $this->embed('form', ['formId' => 1, 'autoBuild'=>true]);?>
  </div>
<?php endif; ?>

</div>
