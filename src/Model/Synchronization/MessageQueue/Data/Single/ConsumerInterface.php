<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single;


interface ConsumerInterface
{
    public function process(Message $update);
}
