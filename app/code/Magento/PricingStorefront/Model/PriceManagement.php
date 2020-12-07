<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Psr\Log\LoggerInterface;

/**
 * Repository for storing data to data storage.
 */
class PriceManagement
{
    /**
     * @var PriceRepository
     */
    private $priceRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PriceBookRepository
     */
    private PriceBookRepository $priceBookRepository;

    /**
     * @param PriceRepository $priceRepository
     * @param PriceBookRepository $priceBookRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        PriceRepository $priceRepository,
        PriceBookRepository $priceBookRepository,
        LoggerInterface $logger
    ) {
        $this->priceRepository = $priceRepository;
        $this->priceBookRepository = $priceBookRepository;
        $this->logger = $logger;
    }

    /**
     * Prepare condition to remove prices.
     *
     * @param int $priceBookId
     * @param array $productIds
     * @return int
     * @throws \Throwable
     */
    public function unassignPrices(string $priceBookId, array $productIds): int
    {
        return $this->priceRepository->delete(
            [
                'pricebook_id = ?' => $priceBookId,
                'product_id IN (?)' => $productIds
            ]
        );
    }

    public function fetchPrices(string $priceBookId = null, array $productId = [])
    {
        $priceBook = $this->priceBookRepository->getById($priceBookId);
        $prices = [];
        $condition = !empty($productId) ? 'product_id IN (' . implode(',', $productId) . ')' : '';

        while (!empty($prices) || empty($priceBook['parent_id'])) {
            // @todo implement correct fallback for each product and each book
            $cond = $condition . ' AND id = ' . $priceBook['id'];
            $prices = $this->priceRepository->get($cond);
        }

        return $prices;
    }
}
