<title><?php $this->print($title);?></title>

<base href="<?php $this->print($base['url']);?>" target="<?php $this->print($base['target']);?>" />

<?php foreach ($custom['top'] as $c): ?>
    <?php $this->print($c); ?>
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

<?php foreach ($link as $linkData): ?>
    <link
    <?php foreach ($linkData as $attribute => $value): ?>
        <?php $this->print(' ' . $attribute . '="' . $value . '"', ['filters'=>'html']); ?>
    <?php endforeach; ?>
    />
<?php endforeach; ?>

<link rel="icon" type="image/<?php $this->print($favicon['imageType'])?>" href="<?php $this->print($favicon['url'])?>">

<?php foreach ($js as $j): ?>
    <?php if (strpos($j[0], '<script') !== false):?>
        <?php if (strpos($j[0], '</script>') == strlen($j[0]) - 9):?>
            <?php $this->print($j[0]); ?>
        <?php endif;?>
    <?php else: ?>
        <script src="<?php $this->print((strpos($j[0], '//') !== false ? '' : \Arembi\Xfw\Core\Router::getHostUrl()) . htmlspecialchars($j[0]))?>" <?php $this->print($j[1] ? ' async' : '')?>></script>
    <?php endif;?>
<?php endforeach;?>

<?php foreach ($css as $c): ?>
    <link rel="stylesheet" href="<?php $this->print((strpos($c, '//') !== false ? '' : \Arembi\Xfw\Core\Router::getHostUrl()) . $c, ['filters'=>'html'])?>" type="text/css">
<?php endforeach;?>

<?php foreach ($custom['bottom'] as $c): ?>
    <?php $this->print($c); ?>
<?php endforeach;?>
