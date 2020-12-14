<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricingStorefront\Model;

use Magento\PricingStorefrontApi\Api\Data\ScopeInterface;

class PriceBookIdBuilder implements PriceBookIdBuilderInterface
{

    /**
     * @ingeridoc
     * @param ScopeInterface $scope
     * @return string
     */
    public function build(ScopeInterface $scope): string
    {
        $websites = array_unique($scope->getWebsite());
        sort($websites,SORT_NUMERIC);
        $customerGroups = array_unique($scope->getCustomerGroup());
        sort($customerGroups,SORT_NUMERIC);

        return sprintf(
            'w[%s]:cg[%s]',
            implode(',', $websites),
            implode(',', $customerGroups)
        );
    }
}
