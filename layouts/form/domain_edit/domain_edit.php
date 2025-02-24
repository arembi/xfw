<?php
namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Router;
?>

<div>
  <h2>Domain #<?php $this->print($fields['domainId']->attribute('value')) ?> szerkesztése</h2>
  <form method="POST" action="<?php $this->print($action) ?>">
    <table>
      <tbody>
        <tr>
          <td>
            <label for="routeId"><?php $this->print($fields['domainId']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['domainId']->tag()) ?>
          </td>
        </tr>
        <tr>
          <td>
            <label for="domain"><?php $this->print($fields['domain']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['domain']->tag()) ?>
          </td>
        </tr>
        <tr>
          <td>
            <label for="protocol"><?php $this->print($fields['protocol']->label()) ?></label>
          </td>
          <td>
            <?php $this->print($fields['protocol']->tag()) ?>
          </td>
        </tr>
        <tr>
          <td>
            <label for="domainSettings"><?php $this->print($fields['domainSettings']->label()) ?></label>
          </td>
          <td>
          <?php if (get_class($fields['domainSettings']) == 'Arembi\Xfw\FormFieldSet'):?>
            <table>
              <tbody>
                <?php foreach ($fields['domainSettings']->fields() as $s):?>
                  <tr>
                    <td>
                      <?php $this->print($s->label()) ?>
                    </td>
                    <td>
                      <?php $this->print($s->tag()) ?>
                    </td>
                <?php endforeach;?>
              </tbody>
            </table>
          <?php else: ?>
            <?php $this->print($fields['domainSettings']->tag()); ?>
          <?php endif; ?>
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
  <h2>Domain #<?php $this->print($fields['domainId']->attribute('value')) ?> törlése</h2>
  
  <?php $this->embed('form', [
    'handlerModule'=>'control_panel',
    'handlerMethod'=>'domain_delete',
    'actionUrl'=>Router::url('+route=' . Router::getMatchedRoute()->id . '?task=domain_list'),
    'layoutVariant'=>'domain_delete_no_header',
    'fields'=>[
      'domainId'=>[
        'fieldType'=>'hidden',
        'attributes'=>[
          'value'=>$fields['domainId']->attribute('value'),
          'readonly'=>true
        ],
        'label'=>'ID'
      ]
    ]
  ]); ?>

</div>
