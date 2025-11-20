<?php if(isset($form)): ?>
    <?php $this->print($form); ?>
<?php else:?>
    <div>
        <?php $this->print(['en'=>'Content doesn\'t exist.', 'hu'=>'A tartalom nem létezik.']); ?>
    </div>
<?php endif;?>