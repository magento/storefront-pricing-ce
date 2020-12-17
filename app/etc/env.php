<?php
return [
    'install' => [
        'date' => '2020-12-17 13:03:16'
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'db' => [
        'connection' => [
            'default' => [
                'host' => 'db',
                'dbname' => 'storefront-pricing-ce',
                'username' => 'root',
                'password' => '',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'driver_options' => [
                    1014 => false
                ]
            ]
        ],
        'table_prefix' => ''
    ],
    'MAGE_MODE' => 'developer'
];
