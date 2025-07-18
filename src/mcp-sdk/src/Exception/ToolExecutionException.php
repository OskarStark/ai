<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\McpSdk\Exception;

use Symfony\AI\McpSdk\Capability\Tool\ToolCall;

final class ToolExecutionException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly ToolCall $toolCall,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(\sprintf('Execution of tool "%s" failed with error: %s', $toolCall->name, $previous?->getMessage() ?? ''), previous: $previous);
    }
}
