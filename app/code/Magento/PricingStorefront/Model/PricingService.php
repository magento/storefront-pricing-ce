<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricingStorefront\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PricingStorefrontApi\Api\Data\AssignPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\GetPricesOutputFatory;
use Magento\PricingStorefrontApi\Api\Data\GetPricesOutputInterface;
use Magento\PricingStorefrontApi\Api\Data\GetPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookAssignPricesResponseFactory;
use Magento\PricingStorefrontApi\Api\Data\PriceBookAssignPricesResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookDeleteRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookDeleteResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookUnassignPricesResponseFactory;
use Magento\PricingStorefrontApi\Api\Data\PriceBookUnassignPricesResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\UnassignPricesRequestInterface;
use Magento\PricingStorefrontApi\Api\PriceBookServiceServerInterface;
use Magento\PricingStorefrontApi\Api\Data\StatusFactory;

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
     * @param PriceBookAssignPricesResponseFactory $assignPricesResponseFactory
     * @param PriceBookUnassignPricesResponseFactory $priceBookUnassignPricesResponseFactory
     * @param StatusFactory $statusFactory
     * @param PriceManagement $priceManagement
     * @param PriceBookRepository $priceBookRepository
     * @param GetPricesOutputFactory $getPricesOutputFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        PriceBookAssignPricesResponseFactory $assignPricesResponseFactory,
        PriceBookUnassignPricesResponseFactory $priceBookUnassignPricesResponseFactory,
        StatusFactory $statusFactory,
        PriceManagement $priceManagement,
        PriceBookRepository $priceBookRepository,
        GetPricesOutputFactory $getPricesOutputFactory,
        LoggerInterface $logger
    ) {
        $this->assignPricesResponseFactory = $assignPricesResponseFactory;
        $this->priceBookUnassignPricesResponseFactory = $priceBookUnassignPricesResponseFactory;
        $this->statusFactory = $statusFactory;
        $this->priceManagement = $priceManagement;
        $this->priceBookRepository = $priceBookRepository;
        $this->getPricesOutputFactory = $getPricesOutputFactory;
        $this->logger = $logger;
    }

    public function buildPriceBookId(PriceBookScopeRequestInterface $request): PriceBookResponseInterface
    {
        // TODO: Implement buildPriceBookId() method.
    }

    public function findPriceBook(PriceBookScopeRequestInterface $request): PriceBookResponseInterface
    {
        // TODO: Implement findPriceBook() method.
    }

    public function createPriceBook(PriceBookRequestInterface $request): PriceBookCreateResponseInterface
    {
        // TODO: Implement createPriceBook() method.
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
}
