<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data\Item;

use Synerise\Integration\MessageQueue\Message\Data\Item;

interface ConsumerInterface
{
    public function process(Item $update);
}
