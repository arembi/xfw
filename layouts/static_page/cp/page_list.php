<style>
    table {
        border-collapse: collapse;
    }
    td, th {
        border: 1px solid #444;
    }
</style>
<header>
    <h1><?php $this->print(['en'=>'List of Static Pages','hu'=>'Statikus oldalak listája']); ?></h1>
</header>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Content</th>
            <th>Route</th>
            <th title="Created By">CB</th>
            <th title="Created At">CA</th>
            <th title="Last Updated">UA</th>
            <th colspan="2">Tools</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($pages as $i => $page):?>
        <tr>
            <td title="ID"><?php $this->print($page->id); ?></td>
            <td title="content">
                <table>
                    <tr>
                        <th>Language</th>
                        <th>Title</th>
                        <th>Excerpt</th>
                        <th>Content</th>
                    </tr>
                <?php foreach ($availableLanguages as $lang) :?>
                    <tr>
                        <td>
                            <?php $this->print($lang[0]); ?>:
                        </td>
                        <td style="font-weight: bold;">
                            <?php $this->print(!empty($page->pageTitle[$lang[0]]) ? $page->pageTitle[$lang[0]] : 'not set'); ?>
                        </td>
                        <td>
                            <?php $this->print(!empty($page->pageExcerpt[$lang[0]]) ? 'set' : 'not set'); ?>
                        </td>
                        <td>
                            <?php $this->print(!empty($page->pageContent[$lang[0]]) ? 'set' : 'not set'); ?>
                        </td>
                    </tr>
                <?php endforeach;?>
                </table>
            </td>
            <td title="route ID">
                <table>
                    <tr>
                        <th colspan="2">
                            <?php $this->print($page->routeId); ?>
                        </th>
                    </tr>
                    <?php foreach ($availableLanguages as $lang): ?>
                    <tr>
                        <td>
                            <?php $this->print($lang[0]); ?>:
                        </td>
                        <td>
                            <?php $this->print($page->route->path[$lang[0]] ?? 'not set'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </td>
            <td title="created by">
                <?php $this->print($page->username); ?>
            </td>
            <td title="created at">
                <?php $this->print($page->createdAt); ?>
            </td>
            <td title="last updated">
                <?php $this->print($page->updatedAt); ?>
            </td>
            <td title="edit">
                <?php $this->print($page->editLink); ?>
            </td>
            <td title="delete">
                <?php $this->print($page->deleteLink); ?>
            </td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>