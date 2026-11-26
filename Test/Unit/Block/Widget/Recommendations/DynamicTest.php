<?php
declare(strict_types=1);

namespace Synerise\Integration\Test\Unit\Block\Widget\Recommendations;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Block\Widget\Recommendations\Dynamic;

class DynamicTest extends TestCase
{
    private function createBlock(array $data): Dynamic
    {
        return new Dynamic(new Json(), $this->createMock(Context::class), $data);
    }

    public function testJsConfigContainsWidgetParams(): void
    {
        $block = $this->createBlock([
            'ajax_url' => '/synerise/ajax/widget_recommendations',
            'campaign_id' => 'IjWrZLM33Ixo',
            'title' => 'Top items',
        ]);

        $this->assertSame(
            [
                'url' => '/synerise/ajax/widget_recommendations',
                'params' => ['campaign_id' => 'IjWrZLM33Ixo', 'title' => 'Top items'],
            ],
            json_decode($block->getJsConfig(), true)
        );
    }

    public function testJsConfigCannotBreakOutOfHtmlAttribute(): void
    {
        $payload = 'x\'><img src=x onerror=alert(1)>"&';
        $block = $this->createBlock([
            'ajax_url' => '/synerise/ajax/widget_recommendations',
            'campaign_id' => $payload,
            'title' => $payload,
            'layout_handle' => $payload,
        ]);

        $json = $block->getJsConfig();

        foreach (["'", '<', '>', '&'] as $char) {
            $this->assertStringNotContainsString($char, $json);
        }
        $this->assertStringNotContainsString('\\"', $json);

        $decoded = json_decode($json, true);
        $this->assertSame($payload, $decoded['params']['campaign_id']);
        $this->assertSame($payload, $decoded['params']['title']);
        $this->assertSame($payload, $decoded['params']['layout_handle']);
    }
}
