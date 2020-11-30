<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// *
// Copyright © Magento, Inc. All rights reserved.
// See COPYING.txt for license details.
namespace Magento\PricingStorefrontApi\Proto;

/**
 */
class PriceBookServiceClient extends \Grpc\BaseStub
{

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * Build Price Book Id based on Scopes.
     * @param \Magento\PricingStorefrontApi\Proto\PriceBookScopeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function buildPriceBookId(
        \Magento\PricingStorefrontApi\Proto\PriceBookScopeRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/buildPriceBookId',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\PriceBookResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Return existing Price Book Id or null if not found.
     * @param \Magento\PricingStorefrontApi\Proto\PriceBookScopeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function findPriceBook(
        \Magento\PricingStorefrontApi\Proto\PriceBookScopeRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/findPriceBook',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\PriceBookResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Creates a new price book. All fields are required. Throws invalid argument error if some argument is missing
     * @param \Magento\PricingStorefrontApi\Proto\PriceBookRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function createPriceBook(
        \Magento\PricingStorefrontApi\Proto\PriceBookRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/createPriceBook',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\PriceBookCreateResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Delete a price book by price book id. Delete all assigned prices to price book.
     * @param \Magento\PricingStorefrontApi\Proto\PriceBookDeleteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function deletePriceBook(
        \Magento\PricingStorefrontApi\Proto\PriceBookDeleteRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/deletePriceBook',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\PriceBookDeleteResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Assign prices of product or custom option to Price Book
     * @param \Magento\PricingStorefrontApi\Proto\AssignPricesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function assignPrices(
        \Magento\PricingStorefrontApi\Proto\AssignPricesRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/assignPrices',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\PriceBookAssignPricesResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Unassign prices of product or custom option from Price Book
     * @param \Magento\PricingStorefrontApi\Proto\UnassignPricesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function unassignPrices(
        \Magento\PricingStorefrontApi\Proto\UnassignPricesRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/unassignPrices',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\PriceBookUnassignPricesResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Get product prices in given Price Book scope
     * @param \Magento\PricingStorefrontApi\Proto\GetPricesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function getPrices(
        \Magento\PricingStorefrontApi\Proto\GetPricesRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/getPrices',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\GetPricesOutput', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Get product prices in given Price Book scope with qty > 1 only
     * @param \Magento\PricingStorefrontApi\Proto\GetPricesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function getTierPrices(
        \Magento\PricingStorefrontApi\Proto\GetPricesRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/magento.pricingStorefrontApi.proto.PriceBookService/getTierPrices',
            $argument,
            ['\Magento\PricingStorefrontApi\Proto\GetPricesOutput', 'decode'],
            $metadata,
            $options
        );
    }
}
