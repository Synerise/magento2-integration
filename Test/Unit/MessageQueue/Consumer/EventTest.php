<?php

namespace Synerise\Integration\Test\Unit\MessageQueue\Consumer;

use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Consumer\Event;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\Observer\Event\CartAddProduct;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as ProductSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as SubscriberSender;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

/**
 * The consumer casts client.customId in queued payloads to a string before sending.
 */
class EventTest extends TestCase
{
    /** @var array|null payload the event sender received */
    private $sentPayload;

    private function createConsumer(): Event
    {
        $eventSender = $this->createMock(EventSender::class);
        $eventSender->method('send')->willReturnCallback(function (string $name, $payload) {
            $this->sentPayload = $payload;
        });

        return new Event(
            $this->createMock(Logger::class),
            $this->createMock(ObjectManagerInterface::class),
            $this->createMock(MessageEncoder::class),
            new Json(),
            $eventSender,
            $this->createMock(CustomerSender::class),
            $this->createMock(ProductSender::class),
            $this->createMock(SubscriberSender::class),
            $this->createMock(DataItemPublisher::class)
        );
    }

    private function message(array $payload): string
    {
        return json_encode([
            'event_name' => CartAddProduct::EVENT,
            'event_payload' => $payload,
            'store_id' => 1,
            'entity_id' => null,
            'retries' => 0,
        ]);
    }

    public function testQueuedNumericCustomIdIsSentAsString(): void
    {
        $this->createConsumer()->process($this->message([
            'client' => ['customId' => 3, 'uuid' => 'u', 'email' => 'a@b.c'],
            'params' => ['sku' => 'X'],
        ]));

        $this->assertSame('3', $this->sentPayload['client']['customId']);
        $this->assertSame('u', $this->sentPayload['client']['uuid']);
    }

    public function testStringCustomIdAndOtherFieldsAreLeftAlone(): void
    {
        $payload = ['client' => ['customId' => '3', 'uuid' => 'u'], 'params' => ['quantity' => 2]];

        $this->createConsumer()->process($this->message($payload));

        $this->assertSame($payload, $this->sentPayload);
    }

    public function testPayloadWithoutCustomIdIsUnchanged(): void
    {
        $payload = ['client' => ['uuid' => 'u'], 'params' => []];

        $this->createConsumer()->process($this->message($payload));

        $this->assertSame($payload, $this->sentPayload);
    }
}
