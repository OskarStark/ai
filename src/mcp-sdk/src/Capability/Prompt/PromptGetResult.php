<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\McpSdk\Capability\Prompt;

final readonly class PromptGetResult
{
    /**
     * @param list<PromptGetResultMessages> $messages
     */
    public function __construct(
        public string $description,
        public array $messages = [],
    ) {
    }
}
