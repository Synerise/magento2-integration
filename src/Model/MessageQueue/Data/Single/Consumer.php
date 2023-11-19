<?php

namespace Synerise\Integration\Model\MessageQueue\Data\Single;

class Consumer
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

    public function process(Message $message)
    {
        $this->consumerFactory->get($message->getModel())->process($message);
    }
}
