<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class PriceRepository
{
    const PRICES_TABLE_NAME = 'prices';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Delete prices by specified condition.
     *
     * @param array $condition
     * @return int
     * @throws \Throwable
     */
    public function delete(array $condition): int
    {
        $connection = $this->resourceConnection->getConnection();
        return (int)$connection->delete($this->getPricesTable(), $condition);
    }

    /**
     * Delete prices by specified condition.
     *
     * @param string $condition
     * @return array
     * @throws \Throwable
     */
    public function get(string $condition): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->getPricesTable())
            ->where($condition);
        return $connection->fetchAll($select);
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
}
