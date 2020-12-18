<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\PricingStorefront\Model\Storage\PriceRepository;
use Psr\Log\LoggerInterface;

/**
 * Class to perform domain logic fo price management.
 */
class PriceManagement
{
    /**
     * @var PriceRepository
     */
    private $priceRepository;

    /**
     * @var PriceBookRepository
     */
    private $priceBookRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * Remove prices.
     *
     * @param string $priceBookId
     * @param array $productIds
     * @return int
     * @throws \Throwable
     */
    public function unassignPrices(string $priceBookId, array $productIds): int
    {
        return $this->priceRepository->delete($priceBookId, $productIds);
    }

    /**
     * Assign product price to price book.
     * Prices are assigned by using following fallback logic:
     * 1. If Price for specific price book exists - it will be updated.
     * 2. If price for specific price book doesn't exists - price for nearest parent price book will be found:
     * - if nearest parent's price the same as new price for such child book - nothing will be updated
     * (price book will then use parent's price)
     * - if nearest parent's price different from new price for such child book - price for such child will be inserted
     * 3. If all price book parents have no assigned prices but price set for default book - price for such child
     * will be inserted
     * 4.Before proceeding assignment of prices - service will check if price for default price book is set - if not -
     * exception will be thrown that default price for product should be set and price will not be assigned
     *
     * @param string $bookId
     * @param array $price
     * @param string|null $parentId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Throwable
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function assignPrice(string $bookId, array $price, ?string $parentId = null) : array
    {
        $priceSearch = [];
        $priceUpdate = [];
        $finalKey = PriceRepository::KEY_FINAL;
        $regularKey = PriceRepository::KEY_REGULAR;
        // @TODO if qty not present what default value should be???
        $qty = $price[PriceRepository::KEY_QTY] ?? 1.0000;
        $productId = $price[PriceRepository::KEY_PRODUCT_ID] ?? null;

        foreach ($price as $key => $value) {
            if (in_array($key, $this->getPriceType(), true)) {
                $priceUpdate[$key . '_' . $finalKey] = $value[$finalKey] ?? null;
                $priceUpdate[$key . '_' . $regularKey] = $value[$regularKey] ?? null;
            }
        }

        if (empty($productId) || empty(array_filter($priceUpdate))) {
            return $priceSearch;
        }

        $origBookId = $bookId;
        $found = false;

        if ($parentId) {
            while (!$found && $bookId && $origBookId) {
                try {
                    $priceSearch = $this->getPriceRow($bookId, $productId, $qty, $parentId);
                } catch (\Throwable $e) {
                    $priceSearch = [];
                }

                if (!empty($priceSearch['price']) && $origBookId === $bookId) {
                    // if new price different as actual but same as parent price - remove actual
                    if (!empty($priceSearch['parent']) && $this->isPricesEqual($price, $priceSearch['parent'])) {
                        $this->unassignPrices($origBookId, [$productId]);
                        $origBookId = null;
                    }

                    // if price for required price book found - update it
                    $found = true;
                    continue;
                }

                if (!empty($priceSearch)) {
                    // compare price with existing parent price and if prices the same - skip update
                    foreach ($priceSearch as $key => $value) {
                        if ($this->isPricesEqual($price, $value)) {
                            $origBookId = null;
                            continue;
                        }
                    }

                    $found = true;
                    continue;
                }

                $bookId = $parentId;
                $priceBook = $this->priceBookRepository->getById($bookId);
                $parentId = $priceBook[PriceBookRepository::KEY_PARENT_ID] ?? null;
            }
        }

        if ($origBookId) {
            $priceUpdate[PriceRepository::KEY_PRODUCT_ID] = $productId;
            $priceUpdate[PriceRepository::KEY_QTY] = (float)$qty;
            $priceUpdate[PriceRepository::KEY_PRICEBOOK_ID] = $origBookId;
            $saveResult = $this->priceRepository->save($priceUpdate);
            $priceUpdate = $saveResult ? $priceUpdate : [];
        }

        return $priceUpdate;
    }

    /**
     * Get price data.
     *
     * @param string $bookId
     * @param string $productId
     * @param string|null $parentId
     * @param float $qty
     * @return array
     */
    public function getPriceRow(string $bookId, string $productId, float $qty, ?string $parentId = null) : array
    {
        return $this->priceRepository->get($bookId, $productId, $parentId, $qty);
    }

    /**
     * Get price for product in price book.
     *
     * @param string $productId
     * @param string $priceBookId
     * @param float $qty
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function fetchPrice(string $productId, string $priceBookId, float $qty = 1.0000) : array
    {
        $priceBookData = $this->priceBookRepository->getById($priceBookId);
        $priceData = [];
        $parentId = $priceBookData[PriceBookRepository::KEY_PARENT_ID] ?? null;
        $origBookId = $priceBookId;
        $found = false;

        while (!$found && $priceBookId && $origBookId) {
            // @TODO qty not provided - so we will get only regular but not tier price??
            try {
                $priceData = $this->getPriceRow($priceBookId, $productId, $qty, $parentId);
            } catch (\Throwable $e) {
                $priceData = [];
            }

            if (!empty($priceData)) {
                $found = true;
                $priceData = $priceData['price'] ?? $priceData['parent'];
                continue;
            }

            if ($priceBookId === $parentId) {
                throw new \RuntimeException(sprintf('Price for product %1 not found.', $productId));
            }

            $priceBookId = $parentId;
            $priceBookData = $this->priceBookRepository->getById($priceBookId);
            $parentId = $priceBookData[PriceBookRepository::KEY_PARENT_ID] ?? PriceBookRepository::DEFAULT_PRICE_BOOK_ID;
        }

        return $priceData;
    }

    /**
     * @return array
     */
    public function getPriceType(): array
    {
        return $this->priceRepository->getPriceType();
    }

    /**
     * @param array $price
     * @param array $secondPrice
     * @return bool
     */
    public function isPricesEqual(array $price, array $secondPrice): bool
    {
        if (empty($price) && empty($secondPrice)) {
            return true;
        }

        $match = 0;
        $finalKey = PriceRepository::KEY_FINAL;
        $regularKey = PriceRepository::KEY_REGULAR;

        foreach ($this->getPriceType() as $priceKey) {
            $dummy = [$finalKey => null, $regularKey => null];
            $type = $price[$priceKey] ?? $dummy;
            $secondType = $secondPrice[$priceKey] ?? $dummy;
            $match += $type[$finalKey] == $secondType[$finalKey] && $type[$regularKey] == $secondType[$regularKey];
        }

        if ($match == count($this->getPriceType())) {
            return true;
        }

        return false;
    }
}
