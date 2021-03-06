<div>Are you sure you want to delete this domain: <strong><?php $this->print($domain) ?></strong>?</div>
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
      <tr>
        <td colspan="2">
          <input type="submit" value="Delete"/>
        </td>
      </tr>
    </tbody>
  </table>
  <?php $this->print($fields['handlerModule']['tag']) ?>
  <?php $this->print($fields['handlerMethod']['tag']) ?>
</form>
