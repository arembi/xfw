<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Path</th>
            <th>Module</th>
            <th>Module Config</th>
            <th title="Clearance Level">CL</th>
            <th colspan="2">Tools</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($routes as $id=>$routeData):?>
        <tr>
            <td title="ID"><?php $this->print($routeData->id);;?></td>
            <td title="path"><?php $this->print($routeData->pathLabel); ?></td>
            <td title="module name"><?php $this->print($routeData->moduleName); ?></td>
            <td title="module configuration for the route"><?php $this->print(json_encode($routeData->moduleConfig)); ?></td>
            <td title="clearance level"><?php $this->print($routeData->clearanceLevel); ?></td>
            <td title="edit"><?php $this->print($routeData->editLink); ?></td>
            <td title="delete"><?php $this->print($routeData->deleteLink); ?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>