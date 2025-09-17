<div>
    <div style="float:left">
        <header style="border-bottom:1px dashed rgba(100,100,100,0.5);">Route</header>
        <div><?php $this->print('ID') ?>: <strong><?php $this->print($route['id']) ?></strong></div>
        <div><?php $this->print(['hu'=>'Elsődleges modul', 'en'=>'Primary Module']) ?>: <strong><?php $this->print($route['moduleName']) ?></strong></div>
        <div><?php $this->print(['hu'=>'moduleConfig akció', 'en'=>'moduleConfig Action']) ?>: <strong><?php $this->print($route['moduleConfig']['action']) ?></strong></div>
        <div><?php $this->print(['hu'=>'Tényleges akció', 'en'=>'Actual Action']) ?>: <strong><?php $this->print($route['action']) ?></strong></div>
    </div>
    <div style="float:left;border-left: 1px solid rgba(100,100,100,0.5);margin-left:1em;padding-left:0.5em;">
        <header style="border-bottom:1px dashed rgba(100,100,100,0.5);">Input Handler</header>
        <div>
        <?php if (!empty($input['inputInfo']['mode'])): ?>
            <div><?php $this->print(['en'=>'Input mode','hu'=>'Input mód']) ?>: <?php $this->print($input['inputInfo']['mode']) ?></div>
            <div>
                <?php $this->print(['en'=>'Input data','hu'=>'Input adat']) ?>:
                <?php foreach ($input['inputInfo']['data'] as $key=>$data): ?>
                    <div style="padding-left:0.5em;"><?php $this->print($key) ?>: <?php $this->print($data) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
            <div><?php $this->print(['en'=>'Status','hu'=>'Státusz']) ?>: <?php $this->print($input['status']) ?></div>
            <div><?php $this->print(['en'=>'Message','hu'=>'Üzenet']) ?>: <?php $this->print($input['message']) ?></div>
            <div><?php $this->print(['en'=>'Data Type','hu'=>'Adat típusa']) ?>: <?php $this->print($input['dataType'])?></div>
        </div>
    </div>
</div>
<div style="clear:both;"></div>