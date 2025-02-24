<div>
  Delete domain <strong><?php $this->print($domain)?>(#<?php $this->print($id)?>)</strong>?
</div>
<form method="POST" action="<?php $this->print($action) ?>">
  <table>
    <tbody>
      <tr>
        <td></td>
        <td>
          <?php $this->print($fields['domainId']->tag()) ?>
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