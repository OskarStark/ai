<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Metadata;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class CacheUsage implements \JsonSerializable
{
    public function __construct(
        public ?int $cacheCreationInputTokens = null,
        public ?int $cacheReadInputTokens = null,
        public ?int $totalCachedTokens = null,
    ) {
    }

    /**
     * @return array{
     *      cache_creation_input_tokens: ?int,
     *      cache_read_input_tokens: ?int,
     *      total_cached_tokens: ?int,
     *  }
     */
    public function jsonSerialize(): array
    {
        return [
            'cache_creation_input_tokens' => $this->cacheCreationInputTokens,
            'cache_read_input_tokens' => $this->cacheReadInputTokens,
            'total_cached_tokens' => $this->totalCachedTokens,
        ];
    }
}
