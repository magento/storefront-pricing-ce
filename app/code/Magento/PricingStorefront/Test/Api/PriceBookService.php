<?php
namespace Magento\PricingStorefront\Test\Api;

use Magento\PricingStorefront\Model\PriceBookIdBuilderInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookCreateRequest;
use Magento\PricingStorefrontApi\Api\Data\PriceBookDeleteRequest;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequest;
use Magento\PricingStorefrontApi\Api\Data\Scope;
use Magento\PricingStorefrontApi\Api\Data\ScopeMapper;
use Magento\PricingStorefrontApi\Api\PriceBookServiceServerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class PriceBookService extends TestCase
{
    /**
     * @var PriceBookIdBuilderInterface
     */
    private $priceBookIdBuilder;

    /**
     * @var PriceBookServiceServerInterface
     */
    private $priceBookService;

    /**
     * @inheridoc
     */
    protected function setUp() :void
    {
        parent::setUp();
        $this->priceBookService = Bootstrap::getObjectManager()->create(PriceBookServiceServerInterface::class);
        $this->priceBookIdBuilder = Bootstrap::getObjectManager()->create(PriceBookIdBuilderInterface::class);
    }

    /**
     * Test Pricing Service's create and find method
     *
     * @magentoDataFixture Magento_PricingStorefront::Test/Api/_files/pricebook.php
     * @magentoDbIsolation disabled
     * @throws \Magento\Framework\Exception\NoSuchEntityException|\Throwable
     */
    public function testValidatePriceBookData()
    {
        /* @var $scope Scope */
        $scope = Bootstrap::getObjectManager()->create(Scope::class);
        /* @var $scopeRequest PriceBookScopeRequest */
        $scopeRequest = Bootstrap::getObjectManager()->create(PriceBookScopeRequest::class);
        $scope->setWebsite(['1','0']);
        $scope->setCustomerGroup(['1']);
        $scopeRequest->setScope($scope);

        /* @var $resultPriceBook PriceBookResponseInterface */
        $resultPriceBook = $this->priceBookService->findPriceBook($scopeRequest);
        self::assertNotEmpty($resultPriceBook);

        $priceBookId = $this->priceBookIdBuilder->build($scope);
        self::assertEquals('0', $resultPriceBook->getStatus()->getCode());
        self::assertEquals('test price book', $resultPriceBook->getPriceBook()->getName());
        self::assertEquals('default', $resultPriceBook->getPriceBook()->getParentId());
        self::assertEquals($priceBookId, $resultPriceBook->getPriceBook()->getId());
    }

    /**
     * Test that one price book with unique set of websites and customer groups can be created
     *
     * @magentoDataFixture Magento_PricingStorefront::Test/Api/_files/pricebook.php
     * @magentoDbIsolation disabled
     */
    public function testPriceBookUniqueCreation()
    {
        /* @var $scope Scope */
        $scope = Bootstrap::getObjectManager()->create(Scope::class);
        $scope->setWebsite(['1','0']);
        $scope->setCustomerGroup(['1','2']);

        /* @var $createRequest PriceBookCreateRequest */
        $createRequest = Bootstrap::getObjectManager()->create(PriceBookCreateRequest::class);
        $createRequest->setScope($scope);
        $createRequest->setName('test price book conflict');
        $createRequest->setParentId('default');

        $result = $this->priceBookService->createPriceBook($createRequest);
        self::assertNotEmpty($result);
        self::assertEquals('1', $result->getStatus()->getCode());
    }

    /**
     * Test price book delete service's method
     *
     * @magentoDataFixture Magento_PricingStorefront::Test/Api/_files/pricebook.php
     * @magentoDbIsolation disabled
     */
    public function testDeletePriceBook()
    {
        /* @var $scopeMapper ScopeMapper */
        $scopeMapper = Bootstrap::getObjectManager()->create(ScopeMapper::class);
        $scope = $scopeMapper->setData(
            [
                'website' => ['1','0'],
                'customer_group' => ['1']
            ]
        )->build();
        $id = $this->priceBookIdBuilder->build($scope);

        /* @var $deleteRequest PriceBookDeleteRequest */
        $deleteRequest = Bootstrap::getObjectManager()->create(PriceBookDeleteRequest::class);
        $deleteRequest->setId($id);
    }
}
