<?php
namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Router;
?>

<div>
  <h2>Útvonal #<?php $this->print($fields['routeId']->attribute('value')) ?> szerkesztése</h2>
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
        <?php if (isset($fields['path-hu'])) :?>
        <tr>
          <td>
            <label for="path-hu"><?php $this->print($fields['path-hu']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['path-hu']->tag()) ?>
          </td>
        </tr>
        <?php endif; ?>
        <?php if (isset($fields['path-en'])) :?>
        <tr>
          <td>
            <label for="path-en"><?php $this->print($fields['path-en']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['path-en']->tag()) ?>
          </td>
        </tr>
        <?php endif; ?>
        <?php if (isset($fields['path-de'])) :?>
        <tr>
          <td>
            <label for="path-de"><?php $this->print($fields['path-de']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['path-de']->tag()) ?>
          </td>
        </tr>
        <?php endif; ?>
        <tr>
          <td>
            <label for="moduleId"><?php $this->print($fields['moduleId']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['moduleId']->tag()) ?>
          </td>
        </tr>
        <tr>
          <td>
            <label for="moduleConfig"><?php $this->print($fields['moduleConfig']->label()) ?></label>
          </td>
          <td>
          <?php if (get_class($fields['moduleConfig']) == 'Arembi\Xfw\FormFieldSet'):?>
            <table>
              <tbody>
                <?php foreach ($fields['moduleConfig']->fields() as $c):?>
                  <tr>
                    <td>
                      <?php $this->print($c->label()) ?>
                    </td>
                    <td>
                      <?php $this->print($c->tag()) ?>
                    </td>
                <?php endforeach;?>
              </tbody>
            </table>
          <?php else: ?>
            <?php $this->print($fields['moduleConfig']->tag()); ?>
          <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td>
            <label for="clearanceLevel"><?php $this->print($fields['clearanceLevel']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['clearanceLevel']->tag()) ?>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <input type="submit" value="Update"/>
          </td>
        </tr>
      </tbody>
    </table>
    <?php $this->print($fields['handlerModule']->tag()) ?>
    <?php $this->print($fields['handlerMethod']->tag()) ?>
  </form>
</div>
<div>
  <br><br>
  <h2>Útvonal #<?php $this->print($fields['routeId']->attribute('value')) ?> törlése</h2>
  
  <?php $this->embed('form', [
    'handlerModule'=>'control_panel',
    'handlerMethod'=>'route_delete',
    'actionUrl'=>Router::url('+route=' . Router::getMatchedRoute()->id),
    'fields'=>[
      'routeId'=>[
        'fieldType'=>'text',
        'attributes'=>[
          'value'=>$fields['routeId']->attribute('value'),
          'readonly'=>true
        ],
        'label'=>'ID'
      ]
    ]
  ]); ?>

</div>
