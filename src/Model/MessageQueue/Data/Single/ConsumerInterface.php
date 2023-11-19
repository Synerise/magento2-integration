<?php

namespace Synerise\Integration\Model\MessageQueue\Data\Single;


interface ConsumerInterface
{
    public function process(Message $update);
}
