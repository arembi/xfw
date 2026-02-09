<?php
$config = [
	'databases'=>[
    'sys'=>[
      'driver'=>'mysql',
      'host'=>'localhost',
      'name'=>'',
      'user'=>'',
      'password'=>'',
      'charset'=>'utf8',
      'collation'=>'utf8_unicode_ci',
      'prefix'=>''
    ]
  ],
  'localhostAutoLogin'=>[
    'enabled'=>true,
    'clearanceLevel'=>99
  ],
  'csrfRequired'=>true,
  'uploadsDir'=>ROOT_DIR . DS . 'xfw_uploads',
  'defaultDomainSettings'=>[
    'inputClearance'=>1,
    'inputHandlerAllowlist'=>null,
    'publicFilesDir'=>'public'
  ]
];
