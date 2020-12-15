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

/**
 * Price Book Repository implementation
 */
class PriceBookRepository
{
    const PRICES_BOOK_TABLE_NAME = 'price_book';
    const DEFAULT_PRICE_BOOK_ID = 'default';

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
        if ($id !== null) {
            $select = $connection->select()
                ->from($this->getPriceBookTable())
                ->where('id = ?', $id);

            $priceBook = $connection->fetchRow($select);
            if (!empty($priceBook)) {
                return $priceBook;
            }
            $this->logger->warning(__('Price book with id "%1" doesn\'t exist. Fallback to default', $id));
        }
        // Get default price book if no id was passed or as a fallback
        $select = $connection->select()
            ->from($this->getPriceBookTable())
            ->where('id = ?', self::DEFAULT_PRICE_BOOK_ID);
        $priceBook = $connection->fetchRow($select);
        if (empty($priceBook)) {
            throw new NoSuchEntityException(__('Default price book doesn\'t exist', $id));
        }

        return $priceBook;
    }

    /**
     * Get price Book by scope
     *
     * @param ScopeInterface $scope
     * @return array
     * @throws NoSuchEntityException
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
    public function create(PriceBookCreateRequestInterface $request) :string
    {
        $priceBookId = $this->priceBookIdBuilder->build($request->getScope());
        $this->validatePriceBookUnique($request->getScope());

        $connection = $this->resourceConnection->getConnection();
        $result = $connection->insert(
            $this->getPriceBookTable(),
            [
                'id' => $priceBookId,
                'name' => $request->getName(),
                'parent_id' => $request->getParentId() ?? self::DEFAULT_PRICE_BOOK_ID,
                'website_ids' => implode(',', $request->getScope()->getWebsite()),
                'customer_group_ids' => implode(',', $request->getScope()->getCustomerGroup())
            ]
        );
        if ($result) {
            return $priceBookId;
        }
    }

    /**
     * Delete price book by id
     *
     * @param string $id
     * @return mixed
     */
    public function delete(string $id)
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->delete(
            $this->getPriceBookTable(),
            ['id = ?' => $id]
        );
    }

    /**
     * Checks if it's only representation of we
     *
     * @param ScopeInterface $scope
     * @throws \ErrorException
     */
    private function validatePriceBookUnique(ScopeInterface $scope) :void
    {
        $connection = $this->resourceConnection->getConnection();
        $priceBook = $connection->fetchAll($this->buildQueryConditions('website_ids', $scope->getWebsite()));
        foreach ($priceBook as $priceBook) {
            $priceBookCustomerGroups = explode(',', $priceBook['customer_group_ids']);
            foreach ($priceBookCustomerGroups as $priceBookCustomerGroup) {
                if (in_array($priceBookCustomerGroup, $scope->getCustomerGroup())) {
                    throw new \ErrorException(
                        sprintf(
                            'Can\'t create Price Book with scope provided. ' .
                            'Customer Group %s is already presented in PriceBook with id: %s',
                            $priceBookCustomerGroup,
                            $priceBook['id']
                        )
                    );
                }
            }
        }
    }

    /**
     * Builds select query to DB
     *
     * @param string $fieldName
     * @param array $ids
     * @return \Magento\Framework\DB\Select
     */
    private function buildQueryConditions(string $fieldName, array $ids): \Magento\Framework\DB\Select
    {
        $cond = [];
        foreach ($ids as $id) {
            $cond[] = $this->resourceConnection->getConnection()
                ->quoteInto($fieldName . ' LIKE ?', "%{$id}%");
        }
        return $this->resourceConnection->getConnection()->select()
            ->from($this->getPriceBookTable())
            ->where(join(' OR ', $cond));
    }
}
