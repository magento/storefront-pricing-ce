<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricingStorefrontConfig\Model;

use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Stdlib\DateTime;

class Installer
{
    /**
     * Configuration for Pricing Service DB connection
     */
    const DB_HOST = 'db-host';
    const DB_NAME = 'db-name';
    const DB_USER = 'db-user';
    const DB_PASSWORD = 'db-password';
    const DB_TABLE_PREFIX = 'db-table-prefix';

    /**
     * @var Writer
     */
    private $deploymentConfigWriter;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ModulesCollector
     */
    private $modulesCollector;

    /**
     * Installer constructor.
     *
     * @param Writer           $deploymentConfigWriter
     * @param DateTime         $dateTime
     * @param ModulesCollector $modulesCollector
     */
    public function __construct(
        Writer $deploymentConfigWriter,
        DateTime $dateTime,
        ModulesCollector $modulesCollector
    ) {
        $this->deploymentConfigWriter = $deploymentConfigWriter;
        $this->dateTime = $dateTime;
        $this->modulesCollector = $modulesCollector;
    }

    /**
     * Create env.php file configuration
     *
     * @param array $optional
     * @throws FileSystemException
     *
     * @deprecated Later we will use another approach
     */
    public function install(array $optional): void
    {
        $config = [
            'app_env' => [
                'install' => [
                    'date' => $this->dateTime->formatDate(true)
                ],
                'resource' => [
                    'default_setup' => [
                        'connection' => 'default'
                    ]
                ],
                'db' => [
                    'connection' => [
                        'default' => [
                            'host' => $optional[self::DB_HOST],
                            'dbname' => $optional[self::DB_NAME],
                            'username' => $optional[self::DB_USER],
                            'password' => $optional[self::DB_PASSWORD],
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'driver_options' => [
                                1014 => false
                            ]
                        ]
                    ],
                    'table_prefix' => $optional[self::DB_TABLE_PREFIX]
                ],
                'MAGE_MODE' => 'developer'
            ],
            'app_config' => [
                'modules' => $this->modulesCollector->execute()
            ]
        ];

        $this->deploymentConfigWriter->saveConfig($config);
    }
}
