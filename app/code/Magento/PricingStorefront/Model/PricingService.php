<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\PricingStorefrontApi\Api\Data\AssignPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\GetPricesOutputInterface;
use Magento\PricingStorefrontApi\Api\Data\GetPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookDeleteRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseMapper;
use Magento\PricingStorefrontApi\Api\Data\GetPricesOutputMapper;
use Magento\PricingStorefrontApi\Api\Data\ProductPriceMapper;
use Magento\PricingStorefrontApi\Api\Data\PriceMapper;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookStatusResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookStatusResponseMapper;
use Magento\PricingStorefrontApi\Api\Data\ScopeInterface;
use Magento\PricingStorefrontApi\Api\Data\AssignPricesRequestMapper;
use Magento\PricingStorefrontApi\Api\Data\ProductPriceArrayMapper;
use Magento\PricingStorefrontApi\Api\Data\UnassignPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\PriceBookServiceServerInterface;
use Magento\PricingStorefrontApi\Proto\PriceBookResponse;
use Psr\Log\LoggerInterface;

/**
 * Class to manage price books
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PricingService implements PriceBookServiceServerInterface
{
    private const STATUS_SUCCESS = '0';
    private const STATUS_FAIL = '1';

    /**
     * @var PriceManagement
     */
    private $priceManagement;

    /**
     * @var PriceBookRepository
     */
    private $priceBookRepository;

    /**
     * @var PriceBookResponseMapper
     */
    private $priceBookResponseMapper;

    /**
     * @var PriceBookStatusResponseMapper
     */
    private $priceBookStatusResponseMapper;

    /**
     * @var ProductPriceMapper
     */
    private $productPriceMapper;

    /**
     * @var PriceMapper
     */
    private $priceMapper;

    /**
     * @var GetPricesOutputMapper
     */
    private $getPricesOutputMapper;

    /**
     * @var AssignPricesRequestMapper
     */
    private $assignPricesRequestMapper;

    /**
     * @var ProductPriceArrayMapper
     */
    private $productPriceArrayMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PriceManagement $priceManagement
     * @param PriceBookRepository $priceBookRepository
     * @param PriceBookResponseMapper $priceBookResponseMapper
     * @param PriceBookStatusResponseMapper $priceBookStatusResponseMapper
     * @param ProductPriceMapper $productPriceMapper
     * @param PriceMapper $priceMapper
     * @param GetPricesOutputMapper $getPricesOutputMapper
     * @param AssignPricesRequestMapper $assignPricesRequestMapper
     * @param ProductPriceArrayMapper $productPriceArrayMapper
     * @param LoggerInterface $logger
     */
    public function __construct(
        PriceManagement $priceManagement,
        PriceBookRepository $priceBookRepository,
        PriceBookResponseMapper $priceBookResponseMapper,
        PriceBookStatusResponseMapper $priceBookStatusResponseMapper,
        ProductPriceMapper $productPriceMapper,
        PriceMapper $priceMapper,
        GetPricesOutputMapper $getPricesOutputMapper,
        AssignPricesRequestMapper $assignPricesRequestMapper,
        ProductPriceArrayMapper $productPriceArrayMapper,
        LoggerInterface $logger
    ) {
        $this->priceManagement = $priceManagement;
        $this->priceBookRepository = $priceBookRepository;
        $this->priceBookResponseMapper = $priceBookResponseMapper;
        $this->priceBookStatusResponseMapper = $priceBookStatusResponseMapper;
        $this->productPriceMapper = $productPriceMapper;
        $this->priceMapper = $priceMapper;
        $this->getPricesOutputMapper = $getPricesOutputMapper;
        $this->assignPricesRequestMapper = $assignPricesRequestMapper;
        $this->productPriceArrayMapper = $productPriceArrayMapper;
        $this->logger = $logger;
    }

    /**
     * Find price book with request
     *
     * @param PriceBookScopeRequestInterface $request
     * @return PriceBookResponseInterface
     * @throws NoSuchEntityException
     */
    public function findPriceBook(PriceBookScopeRequestInterface $request): PriceBookResponseInterface
    {
        try {
            $this->validatePriceBookScopeRequest($request);
            $priceBook = $this->priceBookRepository->getByScope($request->getScope());
            return $this->priceBookResponseMapper->setData(
                [
                    'price_book' => $priceBook,
                    'status' => [
                        'code' => self::STATUS_SUCCESS,
                        'message' => 'Success'
                    ]
                ]
            )->build();
        } catch (\Throwable $e) {
            return $this->priceBookResponseMapper->setData(
                [
                    'status' => [
                        'code' => self::STATUS_FAIL,
                        'message' => $e->getMessage()
                    ]
                ]
            )->build();
        }
    }

    /**
     * Create price book with request
     *
     * @param PriceBookCreateRequestInterface $request
     * @return PriceBookResponseInterface
     */
    public function createPriceBook(PriceBookCreateRequestInterface $request): PriceBookResponseInterface
    {
        try {
            $this->validatePriceBookCreateRequest($request);
            $id = $this->priceBookRepository->create($request);
            $priceBook = $this->priceBookRepository->getById($id);

            return $this->buildPriceBookResponse($priceBook);
        } catch (\Throwable $e) {
            return $this->buildPriceBookResponse(null, $e->getMessage());
        }
    }

    /**
     * Delete price book with request
     *
     * @param PriceBookDeleteRequestInterface $request
     * @return PriceBookStatusResponseInterface
     */
    public function deletePriceBook(PriceBookDeleteRequestInterface $request): PriceBookStatusResponseInterface
    {
        try {
            $this->validatePriceBookDeleteRequest($request);
            $this->priceBookRepository->delete($request->getId());
            $data = [
                'status' => [
                    'code' => self::STATUS_SUCCESS,
                    'message' => 'PriceBook was successfully deleted'
                ]
            ];
        } catch (\Throwable $e) {
            $data = [
                'status' => [
                    'code' => self::STATUS_FAIL,
                    'message' => $e->getMessage()
                ]
            ];
        }
        return $this->priceBookStatusResponseMapper->setData($data)->build();
    }

    /**
     * Service to assign prices to price book.
     *
     * @param AssignPricesRequestInterface $request
     * @return PriceBookStatusResponseInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function assignPrices(AssignPricesRequestInterface $request): PriceBookStatusResponseInterface
    {
        if (empty($request->getPriceBookId())) {
            throw new \InvalidArgumentException('Price Book ID is not present in request.');
        }

        $statusCode = '0';

        if (empty($request->getPrices())) {
            return $this->priceBookStatusResponseMapper->setData([
                'status' => [
                    'code' => $statusCode,
                    'message' => 'Prices not present in request - nothing to process.'
                ]
            ])->build();
        }

        // if price book not exists - exception will be thrown - client side need to send create request before
        $priceBookData = $this->priceBookRepository->getById($request->getPriceBookId());

        $errors = [];
        $bookId = $priceBookData[PriceBookRepository::KEY_ID];
        $parentId = $priceBookData[PriceBookRepository::KEY_PARENT_ID];
        $websites = $priceBookData[PriceBookRepository::KEY_WEBSITE_IDS];
        $customerGroups = $priceBookData[PriceBookRepository::KEY_CUSTOMER_GROUP_IDS];
        $assignToDefault = $bookId === PriceBookRepository::DEFAULT_PRICE_BOOK_ID || (!$websites && !$customerGroups);

        if ($assignToDefault) {
            $bookId = PriceBookRepository::DEFAULT_PRICE_BOOK_ID;
            $parentId = null;
        }

        foreach ($request->getPrices() as $price) {
            $priceArray = $this->productPriceArrayMapper->convertToArray($price);

            try {
                if (!$assignToDefault) {
                    $this->priceManagement->getPriceRow(
                        PriceBookRepository::DEFAULT_PRICE_BOOK_ID,
                        $price->getEntityId(),
                        (float)$price->getQty()
                    );
                }

                // @TODO if price on default is set but not set for some of parent
                // at the moment it will be set for specified book but parents prices will not be updated
                $this->priceManagement->assignPrice($bookId, $priceArray, $parentId);
            } catch (\Throwable $e) {
                $statusCode = 2;
                $errors[] = [
                    'entity_id' => $price->getEntityId(),
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->priceBookStatusResponseMapper->setData([
            'status' => [
                'code' => $statusCode,
                'message' => empty($errors)
                    ? 'Prices was assigned with success.'
                    : \json_encode($errors)
            ]
        ])->build();
    }

    /**
     * Service to remove prices from price book by specified ids.
     *
     * @param UnassignPricesRequestInterface $request
     * @return PriceBookStatusResponseInterface
     */
    public function unassignPrices(UnassignPricesRequestInterface $request): PriceBookStatusResponseInterface
    {
        // @TODO if request is to remove prices for parent - do we need to remove them for all child???
        if (empty($request->getPriceBookId())) {
            throw new \InvalidArgumentException('Price Book ID is not present in request.');
        }

        $statusCode = '0';

        if (empty($request->getIds())) {
            return $this->priceBookStatusResponseMapper->setData([
                'status' => [
                    'code' => $statusCode,
                    'message' => 'Product ids not present in request - nothing to process.'
                ]
            ])->build();
        }

        try {
            $this->priceManagement->unassignPrices((string)$request->getPriceBookId(), $request->getIds());
            $statusMessage = 'Prices was successfully unassigned from price book.';
        } catch (\Throwable $e) {
            $statusCode = 1;
            $statusMessage = 'Unable to unassign prices from price book: ' . $e->getMessage();
            $this->logger->error($statusMessage, ['exception' => $e]);
        }

        return $this->priceBookStatusResponseMapper->setData([
            'status' => [
                'code' => $statusCode,
                'message' => $statusMessage
            ]
        ])->build();
    }

    /**
     * Service to get price for products in specified price books.
     *
     * @param GetPricesRequestInterface $request
     * @return GetPricesOutputInterface
     */
    public function getPrices(GetPricesRequestInterface $request): GetPricesOutputInterface
    {
        if (empty($request->getIds())) {
            throw new \InvalidArgumentException('Product ids not present in request.');
        }

        $priceBookId = $request->getPriceBookId();
        $this->priceBookRepository->getById($request->getPriceBookId());
        $prices = [];

        foreach ($request->getIds() as $productId) {
            try {
                $priceData = $this->priceManagement->fetchPrice($productId, $priceBookId);
            } catch (\Throwable $e) {
                $priceData = [PriceRepository::KEY_PRODUCT_ID => $productId];
                $this->logger->error(
                    sprintf(
                        'Unable to fetch price for product %1 in price book %2: %3',
                        $productId,
                        $priceBookId,
                        $e->getMessage()
                    )
                );
            }

            $prices[] = $this->productPriceMapper->setData($priceData)->build();
        }

        return $this->getPricesOutputMapper->setData(['prices' => $prices])->build();
    }

    public function getTierPrices(GetPricesRequestInterface $request): GetPricesOutputInterface
    {
        // TODO: Implement getTierPrices() method.
    }

    /**
     * PriceBook create request validation
     *
     * @param PriceBookCreateRequestInterface $request
     * @throws \InvalidArgumentException
     */
    private function validatePriceBookCreateRequest( PriceBookCreateRequestInterface $request) :void
    {
        if (empty($request->getName())) {
            throw new \InvalidArgumentException('Price Book name is missing in the request');
        }
        if (empty($request->getParentId())) {
            throw new \InvalidArgumentException('Price Book parent id is missing in the request');
        }
        $this->validatePriceBookScope($request->getScope());
    }

    /**
     * @param PriceBookDeleteRequestInterface $request
     * @throws \InvalidArgumentException
     */
    private function validatePriceBookDeleteRequest(PriceBookDeleteRequestInterface $request): void
    {
        if (empty($request->getId())) {
            throw new \InvalidArgumentException('Price Book id is missing in the request');
        }
    }

    /**
     * @param PriceBookScopeRequestInterface $request
     */
    private function validatePriceBookScopeRequest(PriceBookScopeRequestInterface $request): void
    {
        $this->validatePriceBookScope($request->getScope());
    }

    /**
     * @param ScopeInterface|null $scope
     * @throws \InvalidArgumentException
     */
    private function validatePriceBookScope(?ScopeInterface $scope): void
    {
        if ($scope === null || empty($scope->getWebsite()) || empty($scope->getCustomerGroup())) {
            throw new \InvalidArgumentException('Price Book scope is missing in the request or has empty data');
        }
    }

    /**
     * Builds response object for PriceBook requests
     *
     * @param array  $priceBook
     * @param string $exceptionMessage
     * @return PriceBookResponseInterface
     */
    private function buildPriceBookResponse($priceBook = [], $exceptionMessage = ''): PriceBookResponseInterface
    {
        if (empty($priceBook)) {
            $data = [
                'status' => [
                    'code' => self::STATUS_FAIL,
                    'message' => $exceptionMessage
                ]
            ];
        } else {
            $data = [
                'price_book' => $priceBook,
                'status' => [
                    'code' => self::STATUS_SUCCESS,
                    'message' => 'PriceBook was successfully created'
                ]
            ];
        }

        return $this->priceBookResponseMapper->setData($data)->build();
    }
}
