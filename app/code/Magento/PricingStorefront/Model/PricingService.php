<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PricingStorefrontApi\Api\Data\AssignPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\GetPricesOutputFactory;
use Magento\PricingStorefrontApi\Api\Data\GetPricesOutputInterface;
use Magento\PricingStorefrontApi\Api\Data\GetPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookAssignPricesResponseFactory;
use Magento\PricingStorefrontApi\Api\Data\PriceBookAssignPricesResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookDeleteRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseMapper;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookStatusResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookStatusResponseMapper;
use Magento\PricingStorefrontApi\Api\Data\PriceBookUnassignPricesResponseFactory;
use Magento\PricingStorefrontApi\Api\Data\PriceBookUnassignPricesResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\ScopeInterface;
use Magento\PricingStorefrontApi\Api\Data\StatusFactory;
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
     * @var PriceBookAssignPricesResponseFactory
     */
    private $assignPricesResponseFactory;

    /**
     * @var PriceBookUnassignPricesResponseFactory
     */
    private $priceBookUnassignPricesResponseFactory;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var PriceManagement
     */
    private $priceManagement;

    /**
     * @var PriceBookRepository
     */
    private $priceBookRepository;

    /**
     * @var GetPricesOutputFatory
     */
    private $getPricesOutputFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PriceBookResponseMapper
     */
    private $priceBookResponseMapper;

    /**
     * @var PriceBookStatusResponseMapper
     */
    private $priceBookStatusResponseMapper;

    /**
     * @param PriceBookRepository $priceBookRepository
     * @param PriceBookResponseMapper $priceBookResponseMapper
     * @param PriceBookStatusResponseMapper $priceBookStatusResponseMapper
     * @param LoggerInterface $logger
     */
    public function __construct(
        PriceBookRepository $priceBookRepository,
        PriceBookResponseMapper $priceBookResponseMapper,
        PriceBookStatusResponseMapper $priceBookStatusResponseMapper,
        LoggerInterface $logger
    ) {
        $this->priceBookRepository = $priceBookRepository;
        $this->logger = $logger;
        $this->priceBookResponseMapper = $priceBookResponseMapper;
        $this->priceBookStatusResponseMapper = $priceBookStatusResponseMapper;
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

    public function assignPrices(AssignPricesRequestInterface $request): PriceBookStatusResponseInterface
    {
        /** @var PriceBookAssignPricesResponseInterface $response */
        $response = $this->assignPricesResponseFactory->create();
        /** @var \Magento\PricingStorefrontApi\Api\Data\Status $status */
        $status = $this->statusFactory->create();

        if (empty($request->getPriceBookId())) {
            throw new \InvalidArgumentException('Price Book ID is not present in request.');
        }

        if (empty($request->getPrices())) {
            $status->setCode('success');
            $status->setMessage('Prices not present in request - nothing to process.');
            $response->setStatus($status);
            return $response;
        }

        try {
            $this->priceBookRepository->getById($request->getPriceBookId());
        } catch (NoSuchEntityException $e) {
            throw new \InvalidArgumentException('Price Book doesn\'t exist.');
        }
        // @TODO get price book by id if doesnt exist - throw an exception

        return $response;
    }

    public function unassignPrices(UnassignPricesRequestInterface $request): PriceBookStatusResponseInterface
    {
        /** @var PriceBookUnassignPricesResponseInterface $response */
        $response = $this->priceBookUnassignPricesResponseFactory->create();
        /** @var \Magento\PricingStorefrontApi\Api\Data\Status $status */
        $status = $this->statusFactory->create();

        if (empty($request->getPriceBookId())) {
            throw new \InvalidArgumentException('Price Book ID is not present in request.');
        }

        if (empty($request->getIds())) {
            $status->setCode('success');
            $status->setMessage('Product ids not present in request - nothing to process.');
            $response->setStatus($status);
            return $response;
        }

        try {
            $this->priceManagement->unassignPrices($request->getPriceBookId(), $request->getIds());
            $statusCode = 'success';
            $statusMessage = 'Prices was successfully unassigned from price book.';
        } catch (\Throwable $e) {
            $statusCode = 'error';
            $statusMessage = 'Unable to unasssign prices from price book';
            $this->logger->error($statusMessage, ['exception' => $e]);
        }

        $status->setCode($statusCode);
        $status->setMessage($statusMessage);
        $response->setStatus($status);

        return $response;
    }

    public function getPrices(GetPricesRequestInterface $request): GetPricesOutputInterface
    {
        $response = $this->getPricesOutputFactory->create();
        $priceBookId = $request->getPriceBookId();
        $productIds = $request->getIds();

        return $response;
    }

    public function getTierPrices(GetPricesRequestInterface $request): GetPricesOutputInterface
    {
        // TODO: Implement getTierPrices() method.
    }

    /**
     * PriceBook request validation
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
