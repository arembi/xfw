<div>
<?php if($user->isLoggedIn()): ?>
	<div>
		Hi, <?php $this->print($firstName . ' ' . $lastName . ' (' . $username . ')')?><br>
		Group: <?php $this->print($userGroup) ?> (CL: <?php $this->print($clearanceLevel) ?>)
	</div>
	<div>
		Log out: <?php $this->embed('form', ['formId' => 2]);?>
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
