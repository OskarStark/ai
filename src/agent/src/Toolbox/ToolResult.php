<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Toolbox;

use Symfony\AI\Platform\Result\ToolCall;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final readonly class ToolResult
{
    public function __construct(
        private ToolCall $toolCall,
        private mixed $result,
    ) {
    }

    public function getToolCall(): ToolCall
    {
        return $this->toolCall;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}
