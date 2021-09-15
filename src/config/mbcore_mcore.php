<?php

return [
    'app_install_way' => 'root', // 状态，root 或者 group  ,group时自动在url中加载app_name
    'app_name' => 'SpellGroup',
    'mysql_test' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST_TEST', env('DB_HOST', '127.0.0.1')),
        'port' => env('DB_PORT_TEST', env('DB_PORT', '3306')),
        'database' => env('DB_DATABASE_TEST',  env('DB_DATABASE', 'forge').'_test'),
        'username' => env('DB_USERNAME_TEST', env('DB_USERNAME', 'forge')),
        'password' => env('DB_PASSWORD_TEST', env('DB_PASSWORD', '')),
        'unix_socket' => env('DB_SOCKET_TEST', env('DB_SOCKET', '')),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' =>  env('DB_PREFIX_TEST', env('DB_PREFIX', '')),
        'strict' => true,
        'engine' => null,
    ],
    'storage_url' => 'https://pmstorage.mbcore.com',
    'storage_get_url_api' => '/storage/image',
    'storage_upload_api' => '/api/storage/upload',
    'domain' => '',
    'storage_bucket' => 'mbcore-test',
    'storage_local' =>[
        'baseurl'=>'',
        'group'=>'',
        'tag'=>[
            'goods'=>'',
        ]
    ],
    /*
     |--------------------------------------------------------------------------
     | Default Allow File Types（EasyUpload Plugin）
     |--------------------------------------------------------------------------
     |
     | Here you can customize the white list that allows you to upload file
     | types, empty to unrestricted.
     |
     */
    'Allow_File_Types' =>'*.jpg;*.doc;*.pdf;*.docx;*.xlsx;*.jpeg;*.zip;*.rar;*.psd;*.gif;*.png;*.xls;*.txt;*.bmp;*.dot;*amr;*mp3;',
    'pmcore_url'=> "https://pmcore.mbcore.com/",
    'pmcore_private_key'=>"/mnt/www/PMCore/storage/sso/sso_private.key",
    'core_config_url'=> "https://mplus.mbcore.com/PMCoreClient/",
    'core_category_url'=> "https://pmconfig.mbcore.com/",
    'pmmessage_url'=> "https://pmmessage.mbcore.com/",
    'pmservice_url'=>'http://pmservice.mbcore.com/',
    'base_url'=> [
        '1'=>'https://mplus.mbcore.com/',
        '2'=>'https://prohm.confolsc.com/',
        '3'=>'https://progs.confolsc.com/',
        '4'=>'https://proptgm.confolsc.com/',
        '5'=>'https://prozl.confolsc.com/'
    ],
    /*
     * vaptcha VID
     */
    'vaptcha_vid'=>'',
];