<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Fixtures\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

/**
 * A tool that can have multiple instances with different behavior.
 */
#[AsTool('tool_agent', 'A tool that represents an agent')]
final class ToolMultipleInstances
{
    public function __construct(
        private readonly string $name,
        private readonly string $description,
    ) {
    }

    public function __invoke(): string
    {
        return "Agent {$this->name}: {$this->description}";
    }
}