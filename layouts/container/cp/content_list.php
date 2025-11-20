<style>
    td, th {
        border: 1px dotted #444;
    }
</style>
<header>
    <h1><?php $this->print(['en'=>'List of HTML Contents','hu'=>'HTML taralmak listája']); ?></h1>
</header>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th><?php $this->print(['en'=>'Content','hu'=>'Tartalom']); ?></th>
            <th title="<?php $this->print(['en'=>'Created at','hu'=>'Létrehozva']); ?>">CA</th>
            <th title="<?php $this->print(['en'=>'Last Updated','hu'=>'Utoljára módosítva']); ?>">UA</th>
            <th colspan="2">Tools</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($contents as $i => $content): ?>
        <tr>
            <td title="ID"><?php $this->print($content->id); ?></td>
            <td title="content">
                <table>
                    <tr>
                        <th><?php $this->print(['en'=>'Language','hu'=>'Nyelv']); ?></th>
                        <th><?php $this->print(['en'=>'Title','hu'=>'Cím']); ?></th>
                        <th><?php $this->print(['en'=>'Content','hu'=>'Tartalom']); ?></th>
                    </tr>
                <?php foreach ($availableLanguages as $lang) :?>
                    <tr>
                        <td><?php $this->print($lang[0]); ?>:</td>
                        <td>
                            <?php $this->print(!empty($content->title[$lang[0]]) ? $content->title[$lang[0]] : 'not set'); ?>
                        </td>
                        <td>
                            <?php $this->print(!empty($content->content[$lang[0]]) ? 'set' : 'not set'); ?>
                        </td>
                    </tr>
                <?php endforeach;?>
                </table>
            </td>
            <td title="<?php $this->print(['en'=>'Created at','hu'=>'Létrehozva']); ?>"><?php $this->print($content->created_at); ?></td>
            <td title="<?php $this->print(['en'=>'Last Updated','hu'=>'Utoljára módosítva']); ?>"><?php $this->print($content->updated_at); ?></td>
            <td title="edit"><?php $this->print($content->editLink); ?></td>
            <td title="delete"><?php $this->print($content->deleteLink); ?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>