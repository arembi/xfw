<div>
	<?php $this->print($form); ?>
</div>
<?php $this->embed('form', [
	'handlerModule'=>'static_page',
	'handlerMethod'=>'page_delete',
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
