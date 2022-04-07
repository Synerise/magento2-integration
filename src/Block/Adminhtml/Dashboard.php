<?php
namespace Synerise\Integration\Block\Adminhtml;

use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;

class Dashboard extends \Magento\Backend\Block\Template
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        CustomerCollectionFactory $customerCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        SubscriberCollectionFactory $subscriberCollectionFactory
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        parent::__construct($context);
    }

    protected function _toHtml()
    {
        $this->getChildBlock('catalog-status')->setCollectionFactory($this->productCollectionFactory);
        $this->getChildBlock('customers-status')->setCollectionFactory($this->customerCollectionFactory);
        $this->getChildBlock('orders-status')->setCollectionFactory($this->orderCollectionFactory);
        $this->getChildBlock('newsletter-status')->setCollectionFactory($this->subscriberCollectionFactory);
        return parent::_toHtml();
    }
}
