<form method="POST" action="<?php $this->print($action) ?>">
  <table>
    <tbody>
      <tr>
        <td>
          <label for="id"><?php $this->print($fields['id']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['id']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="id"><?php $this->print($fields['name']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['name']['tag']) ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="type"><?php $this->print($fields['type']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['type']['tag']) ?>
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
        <td>
          <label for="updatedAt"><?php $this->print($fields['updatedAt']['label']) ?></label>
        </td>
        <td>
          <?php $this->print($fields['updatedAt']['tag']) ?>
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
