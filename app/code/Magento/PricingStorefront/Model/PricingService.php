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
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateResponse;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateResponseMapper;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookDeleteRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookDeleteResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseMapper;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookUnassignPricesResponseFactory;
use Magento\PricingStorefrontApi\Api\Data\PriceBookUnassignPricesResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\StatusFactory;
use Magento\PricingStorefrontApi\Api\Data\UnassignPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\PriceBookServiceServerInterface;

use Psr\Log\LoggerInterface;

/**
 * Class to manage price books
 */
class PricingService implements PriceBookServiceServerInterface
{
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
     * @var PriceBookCreateResponseMapper
     */
    private $priceBookCreateResponseMapper;

    /**
     * @var PriceBookResponseMapper
     */
    private $priceBookResponseMapper;

    /**
     * @param PriceBookAssignPricesResponseFactory   $assignPricesResponseFactory
     * @param PriceBookUnassignPricesResponseFactory $priceBookUnassignPricesResponseFactory
     * @param StatusFactory                          $statusFactory
     * @param PriceManagement                        $priceManagement
     * @param PriceBookRepository                    $priceBookRepository
     * @param GetPricesOutputFactory                 $getPricesOutputFactory
     * @param PriceBookCreateResponseMapper          $priceBookCreateResponseMapper
     * @param PriceBookResponseMapper                $priceBookResponseMapper
     * @param LoggerInterface                        $logger
     */
    public function __construct(
        PriceBookAssignPricesResponseFactory $assignPricesResponseFactory,
        PriceBookUnassignPricesResponseFactory $priceBookUnassignPricesResponseFactory,
        StatusFactory $statusFactory,
        PriceManagement $priceManagement,
        PriceBookRepository $priceBookRepository,
        \Magento\PricingStorefrontApi\Api\Data\GetPricesOutputFactory $getPricesOutputFactory,
        PriceBookCreateResponseMapper $priceBookCreateResponseMapper,
        PriceBookResponseMapper $priceBookResponseMapper,
        LoggerInterface $logger
    ) {
        $this->assignPricesResponseFactory = $assignPricesResponseFactory;
        $this->priceBookUnassignPricesResponseFactory = $priceBookUnassignPricesResponseFactory;
        $this->statusFactory = $statusFactory;
        $this->priceManagement = $priceManagement;
        $this->priceBookRepository = $priceBookRepository;
        $this->getPricesOutputFactory = $getPricesOutputFactory;
        $this->logger = $logger;
        $this->priceBookCreateResponseMapper = $priceBookCreateResponseMapper;
        $this->priceBookResponseMapper = $priceBookResponseMapper;
    }

    public function findPriceBook(PriceBookScopeRequestInterface $request): PriceBookResponseInterface
    {
        try {
            $this->validatePriceBookRequest($request);
            $priceBook = $this->priceBookRepository->getByScope($request->getScope());
            return $this->priceBookResponseMapper->setData(
                [
                    'price_book' => $priceBook,
                    'status' => [
                        'code' => '0',
                        'message' => 'Success'
                    ]
                ]
            )->build();
        } catch (\ErrorException|\InvalidArgumentException $e) {
            return $this->priceBookResponseMapper->setData(
                [
                    'status' => [
                        'code' => '1',
                        'message' => $e->getMessage()
                    ]
                ]
            )->build();
        }
    }

    public function createPriceBook(PriceBookCreateRequestInterface $request): PriceBookCreateResponseInterface
    {
        try {
            $this->validatePriceBookRequest($request);
            $id = $this->priceBookRepository->createPriceBook($request);
            $priceBook = $this->priceBookRepository->getById($id);

            return $this->buildPriceBookResponse($priceBook);
        } catch (\ErrorException|\InvalidArgumentException $e) {
            return $this->buildPriceBookResponse(null, $e->getMessage());
        }
    }

    public function deletePriceBook(PriceBookDeleteRequestInterface $request): PriceBookDeleteResponseInterface
    {
        // TODO: Implement deletePriceBook() method.
    }

    public function assignPrices(AssignPricesRequestInterface $request): PriceBookAssignPricesResponseInterface
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

    public function unassignPrices(UnassignPricesRequestInterface $request): PriceBookUnassignPricesResponseInterface
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
     * PriceBook create request validation
     *
     * @param PriceBookCreateRequestInterface|PriceBookScopeRequestInterface $request
     * @throws \InvalidArgumentException
     */
    private function validatePriceBookRequest($request) :void
    {
        if ($request instanceof PriceBookCreateRequestInterface) {
            if (empty($request->getName())) {
                throw new \InvalidArgumentException('Price Book name is missing in the request');
            }
            if (empty($request->getParentId())) {
                throw new \InvalidArgumentException('Price Book parent id is missing in the request');
            }
        }
        if (empty($request->getScope()) ||
            empty($request->getScope()->getWebsite()) ||
            empty($request->getScope()->getCustomerGroup())) {
            throw new \InvalidArgumentException('Price Book scope is missing in the request or has empty data');
        }
    }

    /**
     * Builds response object for PriceBook requests
     *
     * @param array  $priceBook
     * @param string $exceptionMessage
     * @return PriceBookCreateResponse
     */
    private function buildPriceBookResponse($priceBook = [], $exceptionMessage = ''): PriceBookCreateResponse
    {
        if (empty($priceBook)) {
            $data = [
                'status' => [
                    'code' => '1',
                    'message' => $exceptionMessage
                ]
            ];
        } else {
            $data = [
                'price_book' => $priceBook,
                'status' => [
                    'code' => '0',
                    'message' => 'Success'
                ]
            ];
        }

        return $this->priceBookCreateResponseMapper->setData($data)->build();
    }
}
