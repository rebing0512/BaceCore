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
    'base_url'=> [
        '1'=>'https://mplus.mbcore.com/',
    ],
    /*
     * vaptcha VID
     */
    'vaptcha_vid'=>'',
];