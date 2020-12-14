<?php
namespace Magento\PricingStorefront\Test\Api;

use Magento\PricingStorefront\Model\PriceBookIdBuilderInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookArrayMapper;
use Magento\PricingStorefrontApi\Api\Data\PriceBookResponseInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequest;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequestInterface;
use Magento\PricingStorefrontApi\Api\Data\PriceBookScopeRequestMapper;
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
     * Test Pricing Service's create method
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

        self::assertEquals('0', $resultPriceBook->getStatus()->getCode());
        self::assertEquals('test price book', $resultPriceBook->getPriceBook()->getName());
        self::assertEquals('default', $resultPriceBook->getPriceBook()->getParentId());
        self::assertEquals('w[0,1]:cg[1]', $resultPriceBook->getPriceBook()->getId());
    }
}
