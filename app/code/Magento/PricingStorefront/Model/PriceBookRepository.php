<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class PriceBookRepository
{
    const PRICES_BOOK_TABLE_NAME = 'price_book';
    const DEFAULT_PRICES_BOOK_NAME = 'default';

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
     * Get price book by id
     *
     * @param string $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getById(string $id = null): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->getPriceBookTable());

        if ($id) {
            $select->where('id = ?', $id);
        } else {
            $select->where('name = ?', self::DEFAULT_PRICES_BOOK_NAME);
        }

        $priceBook = $connection->fetchRow($select);

        if (empty($priceBook)) {
            throw new NoSuchEntityException(__('Price book with id "%1" doesn\'t exist', $id));
        }

        return $priceBook;
    }

    /**
     * Get price book table name.
     *
     * @return string
     */
    private function getPriceBookTable(): string
    {
        return $this->resourceConnection->getTableName(self::PRICES_BOOK_TABLE_NAME);
    }
}
