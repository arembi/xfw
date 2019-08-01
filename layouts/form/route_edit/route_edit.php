<form method="POST" action="<?php $this->print($action) ?>">
  <table>
    <tbody>
      <tr>
        <td>
          <label for="routeID"><?php $this->print($fields['routeID']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['routeID']['tag']) ?>
        </td>
      </tr>
      <?php if (isset($fields['path-hu'])) :?>
      <tr>
        <td>
          <label for="path-hu"><?php $this->print($fields['path-hu']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['path-hu']['tag']) ?>
        </td>
      </tr>
      <?php endif; ?>
      <?php if (isset($fields['path-en'])) :?>
      <tr>
        <td>
          <label for="path-en"><?php $this->print($fields['path-en']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['path-en']['tag']) ?>
        </td>
      </tr>
      <?php endif; ?>
      <?php if (isset($fields['path-de'])) :?>
      <tr>
        <td>
          <label for="path-de"><?php $this->print($fields['path-de']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['path-de']['tag']) ?>
        </td>
      </tr>
      <?php endif; ?>
      <tr>
        <td>
          <label for="moduleID"><?php $this->print($fields['moduleID']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['moduleID']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="moduleConfig"><?php $this->print($fields['moduleConfig']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['moduleConfig']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="clearanceLevel"><?php $this->print($fields['clearanceLevel']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['clearanceLevel']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <input type="submit" value="Update"/>
        </td>
      </tr>
    </tbody>
  </table>
  <?php $this->print($fields['handlerModule']['tag']) ?>
  <?php $this->print($fields['handlerMethod']['tag']) ?>
</form>
