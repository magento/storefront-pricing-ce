<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/* @var $resourceConnection ResourceConnection */
$resourceConnection = $objectManager->create(ResourceConnection::class);

$resourceConnection->getConnection()->delete(
    \Magento\PricingStorefront\Model\PriceBookRepository::PRICES_BOOK_TABLE_NAME,
    ['name = ?' => 'test price book']
);
