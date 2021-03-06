<form method="POST" action="<?php $this->print($action) ?>">
  <table>
    <tbody>
      <tr>
        <td>
          <label for="domain"><?php $this->print($fields['domain']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['domain']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="protocol"><?php $this->print($fields['protocol']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['protocol']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="settings"><?php $this->print($fields['settings']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['settings']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <input type="submit" value="Create"/>
        </td>
      </tr>
    </tbody>
  </table>
  <?php $this->print($fields['handlerModule']['tag']) ?>
  <?php $this->print($fields['handlerMethod']['tag']) ?>
</form>
