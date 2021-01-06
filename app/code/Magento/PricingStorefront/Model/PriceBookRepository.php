<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Price Book Repository implementation
 */
class PriceBookRepository
{
    public const PRICES_BOOK_TABLE_NAME = 'price_book';
    public const DEFAULT_PRICE_BOOK_ID = 'default';

    public const KEY_ID = 'id';
    public const KEY_PARENT_ID = 'parent_id';
    public const KEY_NAME = 'name';
    public const KEY_WEBSITE_IDS = 'website_ids';
    public const KEY_CUSTOMER_GROUP_IDS = 'customer_group_ids';

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
    public function getById(?string $id = null): array
    {
        if (!$id) {
            $id = self::DEFAULT_PRICE_BOOK_ID;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->getPriceBookTable())
            ->where(self::KEY_ID . ' = ?', $id);

        $priceBook = $connection->fetchRow($select);

        if (empty($priceBook)) {
            $errorMsg = __('Price book with id "%1" doesn\'t exist', $id);
            $this->logger->warning($errorMsg);
            throw new NoSuchEntityException($errorMsg);
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
     * @return string|bool
     * @throws \ErrorException
     */
    public function create(PriceBookCreateRequestInterface $request)
    {
        $priceBookId = $this->priceBookIdBuilder->build($request->getScope());
        $this->validatePriceBookUnique($request->getScope());

        $connection = $this->resourceConnection->getConnection();
        $result = $connection->insert(
            $this->getPriceBookTable(),
            [
                self::KEY_ID => $priceBookId,
                self::KEY_NAME => $request->getName(),
                self::KEY_PARENT_ID => $request->getParentId() ?? self::DEFAULT_PRICE_BOOK_ID,
                self::KEY_WEBSITE_IDS => implode(',', $request->getScope()->getWebsite()),
                self::KEY_CUSTOMER_GROUP_IDS => implode(',', $request->getScope()->getCustomerGroup())
            ]
        );

        if (!$result) {
            throw new \ErrorException(__('Price book wasn\'t created'));
        }

        return $priceBookId;
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
            [self::KEY_ID . ' = ?' => $id]
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
        $priceBooks = $connection->fetchAll(
            $this->buildQueryConditions(self::KEY_WEBSITE_IDS, $scope->getWebsite())
        );

        foreach ($priceBooks as $priceBook) {
            $priceBookCustomerGroups = explode(',', $priceBook[self::KEY_CUSTOMER_GROUP_IDS]);

            foreach ($priceBookCustomerGroups as $priceBookCustomerGroup) {
                if (in_array($priceBookCustomerGroup, $scope->getCustomerGroup())) {
                    throw new \ErrorException(
                        sprintf(
                            'Can\'t create Price Book with scope provided. ' .
                            'Customer Group %s is already presented in PriceBook with id: %s',
                            $priceBookCustomerGroup,
                            $priceBook[self::KEY_ID]
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
     * @return Select
     */
    private function buildQueryConditions(string $fieldName, array $ids): Select
    {
        $cond = [];

        foreach ($ids as $id) {
            $cond[] = $this->resourceConnection->getConnection()
                ->quoteInto($fieldName . ' LIKE ?', "%{$id}%");
        }

        return $this->resourceConnection->getConnection()->select()
            ->from($this->getPriceBookTable())
            ->where(implode(' OR ', $cond));
    }
}
