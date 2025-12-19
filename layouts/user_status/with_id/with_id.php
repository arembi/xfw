<div>
<?php if($user->isLoggedIn()): ?>
	<div>
		Hi, <?php $this->print($firstName . ' ' . $lastName . ' (' . $username . ')')?><br>
		Group: <?php $this->print($userGroup) ?> (CL: <?php $this->print($clearanceLevel) ?>)<br>
		Session ID: <?php $this->print($sessionId); ?>
	</div>
	<div>
		Log out: <?php $this->embed('form', ['formName' => 'logout']);?>
	</div>
<?php else: ?>
	<div>
		Hi, <?php $this->print($firstName)?><br>
	</div>
	<div>
		<?php $this->embed('form', ['formName' => 'login']);?>
	</div>
<?php endif; ?>
</div>
