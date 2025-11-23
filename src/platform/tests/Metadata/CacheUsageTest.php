<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests\Metadata;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Metadata\CacheUsage;

final class CacheUsageTest extends TestCase
{
    public function testItCanBeCreatedWithAllValues()
    {
        $cacheUsage = new CacheUsage(
            cacheCreationInputTokens: 100,
            cacheReadInputTokens: 200,
            totalCachedTokens: 300,
        );

        $this->assertSame(100, $cacheUsage->cacheCreationInputTokens);
        $this->assertSame(200, $cacheUsage->cacheReadInputTokens);
        $this->assertSame(300, $cacheUsage->totalCachedTokens);
    }

    public function testItCanBeCreatedWithPartialValues()
    {
        $cacheUsage = new CacheUsage(
            cacheCreationInputTokens: 100,
        );

        $this->assertSame(100, $cacheUsage->cacheCreationInputTokens);
        $this->assertNull($cacheUsage->cacheReadInputTokens);
        $this->assertNull($cacheUsage->totalCachedTokens);
    }

    public function testItCanBeCreatedWithNoValues()
    {
        $cacheUsage = new CacheUsage();

        $this->assertNull($cacheUsage->cacheCreationInputTokens);
        $this->assertNull($cacheUsage->cacheReadInputTokens);
        $this->assertNull($cacheUsage->totalCachedTokens);
    }

    public function testItImplementsJsonSerializable()
    {
        $cacheUsage = new CacheUsage(
            cacheCreationInputTokens: 100,
            cacheReadInputTokens: 200,
            totalCachedTokens: 300,
        );

        $expected = [
            'cache_creation_input_tokens' => 100,
            'cache_read_input_tokens' => 200,
            'total_cached_tokens' => 300,
        ];

        $this->assertSame($expected, $cacheUsage->jsonSerialize());
    }

    public function testJsonSerializeHandlesNullValues()
    {
        $cacheUsage = new CacheUsage(
            cacheCreationInputTokens: 100,
        );

        $expected = [
            'cache_creation_input_tokens' => 100,
            'cache_read_input_tokens' => null,
            'total_cached_tokens' => null,
        ];

        $this->assertSame($expected, $cacheUsage->jsonSerialize());
    }
}
