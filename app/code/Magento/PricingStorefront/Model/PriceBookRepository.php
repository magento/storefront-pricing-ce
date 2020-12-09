<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\ScopeInterface;
use Psr\Log\LoggerInterface;

class PriceBookRepository
{
    const PRICES_BOOK_TABLE_NAME = 'price_book';
    const DEFAULT_PRICES_BOOK_ID = 'default';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PriceBookIdBuilderInterface
     */
    private $priceBookIdBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param PriceBookIdBuilderInterface $priceBookIdBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        PriceBookIdBuilderInterface $priceBookIdBuilder,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->priceBookIdBuilder = $priceBookIdBuilder;
    }

    /**
     * Get price book by id
     *
     * @param string|null $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getById(string $id = null): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->getPriceBookTable())
            ->where('id = ?', $id ?? self::DEFAULT_PRICES_BOOK_ID);

        $priceBook = $connection->fetchRow($select);

        if (empty($priceBook)) {
            throw new NoSuchEntityException(__('Price book with id "%1" doesn\'t exist', $id));
        }

        return $priceBook;
    }

    /**
     * @param ScopeInterface $scope
     * @return array
     */
    public function getByScope(ScopeInterface $scope) : array
    {
        return $this->getById($this->priceBookIdBuilder->build($scope));
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

    /**
     * Create price book and save to DB
     *
     * @param PriceBookCreateRequestInterface $request
     * @return string
     * @throws \ErrorException
     */
    public function createPriceBook(PriceBookCreateRequestInterface $request) :string
    {
        $priceBookId = $this->priceBookIdBuilder->build($request->getScope());
        $this->validatePriceBookIdUnique($priceBookId);

        $connection = $this->resourceConnection->getConnection();
        $result = $connection->insert(
            $this->getPriceBookTable(),
            [
                'id' => $priceBookId,
                'name' => $request->getName(),
                'parent_id' => $request->getParentId(),
                'website_ids' => implode(',', $request->getScope()->getWebsite()),
                'customer_group_ids' => implode(',', $request->getScope()->getCustomerGroup())
            ]
        );
        if ($result) {
            return $priceBookId;
        }
    }

    /**
     * Checks if it's only record of Price Book with given id exists
     *
     * @param string $id
     * @throws \ErrorException
     */
    private function validatePriceBookIdUnique(string $id) :void
    {
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()
            ->from($this->getPriceBookTable())
            ->where('id = ?', $id);

        $priceBook = $connection->fetchRow($query);
        if ($priceBook) {
            throw new \ErrorException(
                sprintf(
                    'Can\'t create Price Book. The record with id=%s is already exists in database',
                    $id
                )
            );
        }
    }
}
