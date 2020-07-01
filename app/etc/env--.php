<?php
return [
    'backend' => [
        'frontName' => 'admin'
    ],
    'crypt' => [
        'key' => '1bcac53e3c51cff743fb7d156156e559'
    ],
    'db' => [
        'table_prefix' => '',
        'connection' => [
            'default' => [
                'host' => 'localhost',
                'dbname' => 'khetbaari',
                'username' => 'root',
                'password' => 'Omnamashiva7#',
                'active' => '1',
                'driver_options' => [

                ]
            ]
        ]
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'developer',
    'session' => [
        'save' => 'files'
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'id_prefix' => 'eeb_'
            ],
            'page_cache' => [
                'id_prefix' => 'eeb_'
            ]
        ]
    ],
    'lock' => [
        'provider' => 'db',
        'config' => [
            'prefix' => null
        ]
    ],
    'cache_types' => [
        'config' => 1,
        'layout' => 0,
        'block_html' => 0,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'compiled_config' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'google_product' => 1,
        'full_page' => 0,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'translate' => 1,
        'config_webservice' => 1
    ],
    'downloadable_domains' => [
        'localhost'
    ],
    'install' => [
        'date' => 'Sat, 25 Apr 2020 15:41:41 +0000'
    ]
];
