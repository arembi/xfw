<title><?php $this->print($title);?></title>

<?php if (!empty($base['url'])): ?>
    <base href="<?php $this->print($base['url']);?>" target="<?php $this->print($base['target']);?>" />
<?php endif; ?>

<?php foreach ($custom['top'] as $ct): ?>
    <?php $this->print($ct); ?>
<?php endforeach;?>

<?php if (!empty($meta['charset'])): ?>
    <meta charset="<?php $this->print($meta['charset']);?>">
<?php endif;?>

<?php if (!empty($meta['name'])): ?>
    <?php foreach ($meta['name'] as $name => $content): ?>
		<meta name="<?php $this->print($name); ?>" content="<?php $this->print($content); ?>"/>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($meta['http-equiv'])): ?>
    <?php foreach ($meta['http-equiv'] as $name => $content): ?>
		<meta name="<?php $this->print($name); ?>" content="<?php $this->print($content); ?>"/>
    <?php endforeach; ?>
<?php endif; ?>

<?php foreach ($link as $attributes): ?>
    <link <?php $this->print($attributes); ?>/>
<?php endforeach; ?>

<?php foreach ($javascript as $jsTag): ?>
        <script <?php $this->print($jsTag['attributes']); ?>><?php $this->print($jsTag['content'])?></script>
<?php endforeach;?>

<?php foreach ($css as $c): ?>
    <link rel="stylesheet" href="<?php $this->print($c)?>" type="text/css">
<?php endforeach;?>

<?php foreach ($custom['bottom'] as $cb): ?>
    <?php $this->print($cb); ?>
<?php endforeach;?>
