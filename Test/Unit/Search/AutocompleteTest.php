<?php
declare(strict_types=1);

namespace Synerise\Integration\Test\Unit\Search;

use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Search\Autocomplete;
use Synerise\Integration\Search\Autocomplete\DataProviderResolver;

class AutocompleteTest extends TestCase
{
    /**
     * @dataProvider stateProvider
     */
    public function testQueryParamSelectsState(?string $q, string $expectedFirstState): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('q')->willReturn($q);

        $calls = [];
        $resolver = $this->createMock(DataProviderResolver::class);
        $resolver->method('resolve')->willReturnCallback(function ($state) use (&$calls) {
            $calls[] = $state;
            return [];
        });

        (new Autocomplete($resolver, $request))->getItems();

        $this->assertSame($expectedFirstState, $calls[0]);
        $this->assertSame('no_results', $calls[1]);
    }

    public static function stateProvider(): array
    {
        return [
            'no query -> zero_state' => [null, 'zero_state'],
            'empty query -> default' => ['', 'default'],
            'with query -> default'  => ['shoes', 'default'],
        ];
    }
}
