<?php

namespace Synerise\Integration\Test\Integration\Observer\Event\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\Framework\Event\Config as EventConfig;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Api\Event\Cart as CartHelper;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Observer\Event\Cart\AddProduct;

class AddProductObserverTest extends \PHPUnit\Framework\TestCase
{
    const STORE_ID = 1;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var EventConfig
     */
    private $eventConfig;

    /**
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Collection
     */
    private $options;

    /**
     * @var mixed
     */
    private $eavConfig;

    /**
     * @var Event
     */
    private $event;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(EventConfig::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->options = $this->objectManager->create(Collection::class);
        $this->eavConfig = $this->objectManager->get(EavConfig::class);
        $this->cartHelper = $this->objectManager->get(CartHelper::class);
        $this->event = $this->objectManager->create(Event::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('sales_quote_add_item');

        $this->assertArrayHasKey('synerise_checkout_cart_add_product_complete', $observers);
        $this->assertSame(
            AddProduct::class,
            $observers['synerise_checkout_cart_add_product_complete']['instance']
        );
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSendAddToCartEvent()
    {
        $product = $this->productRepository->get('configurable', true, null, true);

        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'test_configurable');
        $option = $this->options->setAttributeFilter($attribute->getId())->getFirstItem();

        $requestInfo = new \Magento\Framework\DataObject(
            [
                'product' => 1,
                'selected_configurable_option' => 1,
                'qty' => 1,
                'super_attribute' => [
                    $attribute->getId() => $option->getId()
                ]
            ]
        );

        $quote = $this->quoteFactory->create();
        $quoteItem = $quote->addProduct($product, $requestInfo);

        $uuid = (string) Uuid::Uuid4();

        list ($body, $statusCode, $headers) = $this->event->sendEvent(
            AddProduct::EVENT,
            $this->cartHelper->prepareAddToCartRequest(
                $quoteItem,
                AddProduct::EVENT,
                $uuid
            ),
            self::STORE_ID
        );

        $this->assertEquals(202, $statusCode);
    }
}