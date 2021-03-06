<div class="user-status">
<?php if($user->isLoggedIn()): ?>
  <div>
    Hi, <?php $this->print($firstName . ' ' . $lastName . ' (' . $username . ')')?><br>
    Group: <?php $this->print($userGroup) ?> (CL: <?php $this->print($clearanceLevel) ?>)
  </div>
  <div>
    <?php $this->embed('form', ['ID' => 2]);?>
  </div>
<?php else: ?>
  <div>
    Hi, <?php $this->print($firstName)?><br>
  </div>
  <div>
    <?php $this->embed('form', ['ID' => 1]);?>
  </div>
<?php endif; ?>

</div>
