<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricingStorefront\Model;

use Magento\PricingStorefrontApi\Api\Data\ScopeInterface;

/**
 * Algorithm to generate Pricebook ID from given Scope object
 */
interface PriceBookIdBuilderInterface
{
    /**
     * Builds id from scope
     *
     * @param ScopeInterface $scope
     * @return string
     */
    public function build(ScopeInterface $scope) :string;
}
