<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Synerise\Integration\MessageQueue\Consumer\Data\Item\ConsumerFactory;
use Synerise\Integration\MessageQueue\Message\Data\Item as ItemMessage;

class Item
{
    /**
     * @var ConsumerFactory
     */
    private $consumerFactory;

    public function __construct(
        ConsumerFactory $consumerFactory
    ) {
        $this->consumerFactory = $consumerFactory;
    }

    public function process(ItemMessage $message)
    {
        $this->consumerFactory->get($message->getModel())->process($message);
    }
}
