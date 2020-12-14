<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

use Magento\PricingStorefront\Model\PriceBookRepository;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateRequestMapper;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/* @var $priceBookRepository PriceBookRepository */
$priceBookRepository = $objectManager->create(PriceBookRepository::class);
/* @var $priceBookCreateRequestMapper PriceBookCreateRequestMapper */
$priceBookCreateRequestMapper = $objectManager->create(PriceBookCreateRequestMapper::class);
$priceBookData = [
    'name' => 'test price book',
    'parent_id' => 'default',
    'scope' => [
        'website' => [
            '1','0'
        ],
        'customer_group' => [
            '1'
        ]
    ]
];

try {
    $priceBookRepository->create(
        $priceBookCreateRequestMapper->setData($priceBookData)->build()
    );
} catch (ErrorException $e) {
    //Exception
}
