<form method="POST" action="<?php $this->print($action) ?>">
  <table>
    <tbody>
      <tr>
        <td>
          <label for="ID"><?php $this->print($fields['ID']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['ID']['tag']) ?>
        </td>
      </tr>
      <?php foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang): ?>
      <tr>
        <td>
          <label for="pageTitle-<?php echo $lang[0]?>"><?php $this->print($fields['pageTitle-' . $lang[0]]['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['pageTitle-' . $lang[0]]['tag']) ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang): ?>
      <tr>
        <td>
          <label for="pageContent-<?php echo $lang[0]?>"><?php $this->print($fields['pageContent-' . $lang[0]]['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['pageContent-' . $lang[0]]['tag']) ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr>
        <td>
          <label for="createdBy"><?php $this->print($fields['createdBy']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['createdBy']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="routeID"><?php $this->print($fields['routeID']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['routeID']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="createdAt"><?php $this->print($fields['createdAt']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['createdAt']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <input type="submit" value="Módosít"/>
        </td>
      </tr>
    </tbody>
  </table>
  <?php $this->print($fields['handlerModule']['tag']) ?>
  <?php $this->print($fields['handlerMethod']['tag']) ?>
</form>
