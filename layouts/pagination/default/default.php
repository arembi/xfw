<nav <?php echo $id, $class, $style, $etc ?>>
    <?php foreach ($links as $l):
        $this->a($l['href'], $l['anchor'], $l);
    endforeach;?>
</nav>
