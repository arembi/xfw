<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Domain</th>
            <th>Protocol</th>
            <th colspan="2">Tools</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($domains as $id=>$domainData):?>
        <tr>
            <td title="ID"><?php echo $id;?></td>
            <td title="domain name"><?php echo $domainData['domain']?></td>
            <td title="domain protocol"><?php echo $domainData['protocol']?></td>
            <td title="edit"><?php echo $domainData['editLink'] ?></td>
            <td title="delete"><?php echo $domainData['deleteLink'] ?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>