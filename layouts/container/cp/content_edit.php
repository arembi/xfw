<div>
    <?php $this->print($form); ?>
</div>
<?php $this->embed('form', [
    'handlerModule'=>'container',
    'handlerMethod'=>'content_delete',
    'actionUrl'=>$upUrl,
    'fields'=>[
      'id'=>[
        'fieldType'=>'text',
        'attributes'=>[
          'value'=>$form->fields()->field('id')->attribute('value'),
          'readonly'=>true
        ],
        'label'=>'ID'
      ]
    ]
  ]); ?>
