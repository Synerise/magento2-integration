<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Event;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\WishlistFactory;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Observer\Event\WishlistAddProduct;
use Synerise\Integration\SyneriseApi\Mapper\Event\WishlistAdd;

class WishlistAddTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;

    const LABEL = 'Customer added product to favourites';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var WishlistAdd
     */
    private $mapper;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->wishlistFactory = $this->objectManager->get(WishlistFactory::class);
        $this->wishlistResource = $this->objectManager->get(WishlistResourceModel::class);

        $this->mapper = $this->objectManager->create(WishlistAdd::class);
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareRequest(): void
    {
        $product = $this->productRepository->get('simple');

        /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, self::FIXTURE_CUSTOMER_ID, 'customer_id');

        $request = $this->mapper->prepareRequest(
            WishlistAddProduct::EVENT,
            $wishlist,
            $product
        );

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(EventClientAction::class, $request);

        $this->assertEquals(self::LABEL, $request->getLabel());

        $params = $request->getParams();
        $this->assertEquals($product->getSku(), $params['sku']);
        $this->assertEquals($product->getName(), $params['name']);
        $this->assertEquals($product->getUrlInStore(), $params['productUrl']);

        $client = $request->getClient();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals(1, $client->getCustomId());
        $this->assertNull($client->getId());
        $this->assertNull($client->getUuid());
        $this->assertNull($client->getEmail());
    }
}
