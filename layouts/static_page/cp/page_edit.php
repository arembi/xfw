<div>
    <?php $this->print($form); ?>
</div>
<?php $this->embed('form', [
    'handlerModule'=>'static_page',
    'handlerMethod'=>'page_delete',
    'actionUrl'=>Router::url('+route=' . Router::getMatchedRoute()->id),
    'fields'=>[
      'pageId'=>[
        'fieldType'=>'text',
        'attributes'=>[
          'value'=>$fields['pageId']->attribute('value'),
          'readonly'=>true
        ],
        'label'=>'ID'
      ]
    ]
  ]); ?>
