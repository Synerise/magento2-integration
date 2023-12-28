<?php

namespace Synerise\Integration\Ui\Component\Action;

class StoreScopedAction extends \Magento\Ui\Component\Action
{
    protected $urlBuilder;
    protected $request;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);

        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

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
