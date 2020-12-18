<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model\Storage;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PricingStorefront\Model\PriceBookRepository;
use Psr\Log\LoggerInterface;

class PriceRepository
{
    const PRICES_TABLE_NAME = 'prices';

    const KEY_ID = 'id';
    const KEY_PRODUCT_ID = 'entity_id';
    const KEY_PRICEBOOK_ID = 'pricebook_id';
    const KEY_REGULAR = 'regular';
    const KEY_FINAL = 'final';
    const KEY_MAXIMUM_PRICE = 'maximum_price';
    const KEY_MINIMUM_PRICE = 'minimum_price';
    const KEY_QTY = 'qty';

    const PRICE_FIELDS = [
        self::KEY_ID,
        self::KEY_PRODUCT_ID,
        self::KEY_PRICEBOOK_ID,
        self::KEY_MINIMUM_PRICE . '_' . self::KEY_REGULAR,
        self::KEY_MINIMUM_PRICE . '_' . self::KEY_FINAL,
        self::KEY_MAXIMUM_PRICE . '_' . self::KEY_REGULAR,
        self::KEY_MAXIMUM_PRICE . '_' . self::KEY_FINAL,
        self::KEY_QTY
    ];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var PriceBookRepository
     */
    private $priceBookRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $parentColumns = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param PriceBookRepository $priceBookRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        PriceBookRepository $priceBookRepository,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->priceBookRepository = $priceBookRepository;
        $this->logger = $logger;
    }

    /**
     * Delete prices by specified condition.
     *
     * @param string $priceBookId
     * @param array $productIds
     * @return int
     * @throw \Throwable
     */
    public function delete(string $priceBookId, array $productIds): int
    {
        if (empty($productIds)) {
            throw new \InvalidArgumentException('Product ids not provided.');
        }

        $condition[self::KEY_PRODUCT_ID . ' IN (?)'] = $productIds;

        if ($priceBookId) {
            $condition[self::KEY_PRICEBOOK_ID . ' = ?'] = $priceBookId;
        }

        $connection = $this->resourceConnection->getConnection();
        return (int)$connection->delete($this->getPricesTable(), $condition);
    }

    /**
     * Get price by price book id and product id.
     *
     * @param string $priceBookId
     * @param string $productId
     * @param string|null $parentId
     * @param float|null $qty
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(string $priceBookId, string $productId, ?string $parentId = null, ?float $qty = 1): array
    {
        $qty = number_format((float)$qty, 4, '.', '');
        $tableName = $this->getPricesTable();

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($tableName);

        if ($parentId) {
            $select->joinLeft(
                ['parent' => $tableName],
                'parent.' . self::KEY_PRICEBOOK_ID . ' = "' . $parentId . '" AND '
                . 'parent.' . self::KEY_PRODUCT_ID . ' = "' . $productId . '" AND '
                . 'parent.' . self::KEY_QTY . ' = ' . $qty,
                $this->getParentColumns()
            );
        }

        $select->where($tableName . '.' . self::KEY_PRICEBOOK_ID . ' = ?', $priceBookId)
            ->where($tableName . '.' . self::KEY_PRODUCT_ID . ' = ?', $productId)
            ->where($tableName . '.' . self::KEY_QTY . ' = ?', $qty);

        $price = $connection->fetchRow($select);

        if (empty($price)) {
            throw new NoSuchEntityException(
                __('Price for price book %1 and product %2 doesn\'t exist', $priceBookId, $productId)
            );
        }

        return $this->formatResponse($price, $parentId);
    }

    /**
     * Prepare parent price columns.
     *
     * @return array
     */
    public function getParentColumns(): array
    {
        if (empty($this->parentColumns)) {
            foreach (self::PRICE_FIELDS as $field) {
                $this->parentColumns['parent_' . $field] = 'parent.' . $field;
            }
        }

        return $this->parentColumns;
    }

    /**
     * Format response to match dto format.
     *
     * @param array $price
     * @param string|null $parentId
     * @return array
     */
    private function formatResponse(array $price, ?string $parentId = null) : array
    {
        $priceFields = array_flip(self::PRICE_FIELDS);
        $result['price'] = array_filter(
            array_combine(self::PRICE_FIELDS, array_intersect_key($price, $priceFields))
        );

        if ($parentId) {
            $result['parent'] = array_filter(
                array_combine(self::PRICE_FIELDS, array_intersect_key($price, $this->getParentColumns()))
            );
        }

        foreach ($result as $key => $value) {
            foreach ($this->getPriceType() as $type) {
                $regular = $type . '_' . self::KEY_REGULAR;
                $final = $type . '_' . self::KEY_FINAL;

                if (!empty($value[$regular]) || !empty($value[$final])) {
                    $result[$key][$type] = [
                        self::KEY_REGULAR => $value[$regular],
                        self::KEY_FINAL => $value[$final]
                    ];

                    unset($value[$regular], $value[$final]);
                }
            }
        }

        return $result;
    }

    /**
     * Save price to storage.
     *
     * @param array $priceUpdate
     * @return int
     */
    public function save(array $priceUpdate): int
    {
        return $this->resourceConnection->getConnection()
            ->insertOnDuplicate(
                $this->getPricesTable(),
                [$priceUpdate],
                [
                    self::KEY_MINIMUM_PRICE . '_' . self::KEY_FINAL,
                    self::KEY_MINIMUM_PRICE . '_' . self::KEY_REGULAR,
                    self::KEY_MAXIMUM_PRICE . '_' . self::KEY_FINAL,
                    self::KEY_MAXIMUM_PRICE . '_' . self::KEY_REGULAR
                ]
            );
    }

    /**
     * Get price table name.
     *
     * @return string
     */
    private function getPricesTable(): string
    {
        return $this->resourceConnection->getTableName(self::PRICES_TABLE_NAME);
    }

    /**
     * @return string[]
     */
    public function getPriceType(): array
    {
        return [
            self::KEY_MAXIMUM_PRICE,
            self::KEY_MINIMUM_PRICE
        ];
    }
}
