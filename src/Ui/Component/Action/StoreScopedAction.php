<?php

namespace Synerise\Integration\Ui\Component\Action;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action;

class StoreScopedAction extends Action
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param ContextInterface $context
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param array|\JsonSerializable $actions
     */
    public function __construct(
        ContextInterface $context,
        RequestInterface $request,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);

        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function prepare()
    {
        parent::prepare();

        $config = $this->getConfiguration();
        $params = [];

        if ($this->request->getParam('store')) {
            $params['store'] = $this->request->getParam('store');
        }

        $config['url'] = $this->urlBuilder->getUrl($config['urlPath'], $params);
        $this->setData('config', $config);
    }
}
