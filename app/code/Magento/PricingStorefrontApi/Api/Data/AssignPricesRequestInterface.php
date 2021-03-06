<?php
# Generated by the Magento PHP proto generator.  DO NOT EDIT!

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PricingStorefrontApi\Api\Data;

/**
 * Autogenerated description for AssignPricesRequest interface
 *
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 */
interface AssignPricesRequestInterface
{
    /**
     * Autogenerated description for getPrices() interface method
     *
     * @return \Magento\PricingStorefrontApi\Api\Data\ProductPriceInterface[]
     */
    public function getPrices(): array;

    /**
     * Autogenerated description for setPrices() interface method
     *
     * @param \Magento\PricingStorefrontApi\Api\Data\ProductPriceInterface[] $value
     * @return void
     */
    public function setPrices(array $value): void;

    /**
     * Autogenerated description for getPriceBookId() interface method
     *
     * @return string
     */
    public function getPriceBookId(): string;

    /**
     * Autogenerated description for setPriceBookId() interface method
     *
     * @param string $value
     * @return void
     */
    public function setPriceBookId(string $value): void;
}
