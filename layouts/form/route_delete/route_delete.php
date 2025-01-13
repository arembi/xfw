<form method="POST" action="<?php $this->print($action) ?>">
  <table>
    <tbody>
      <tr>
        <td>
          <label for="routeId"><?php $this->print($fields['routeId']->label()) ?></label>
        </td>
        <td>
          <?php $this->print($fields['routeId']->tag()) ?>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <input type="submit" value="Delete"/>
        </td>
      </tr>
    </tbody>
  </table>
  <?php $this->print($fields['handlerModule']->tag()) ?>
  <?php $this->print($fields['handlerMethod']->tag()) ?>
</form>