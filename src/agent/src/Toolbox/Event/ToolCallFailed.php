<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Toolbox\Event;

use Symfony\AI\Platform\Tool\Tool;

/**
 * Dispatched after successfully invoking a tool.
 */
final readonly class ToolCallFailed
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public object $tool,
        public Tool $metadata,
        public array $arguments,
        public \Throwable $exception,
    ) {
    }
}
