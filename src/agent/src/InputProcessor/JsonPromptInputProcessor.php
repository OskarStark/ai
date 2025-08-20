<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\InputProcessor;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\AI\Agent\Input;
use Symfony\AI\Agent\InputProcessorInterface;
use Symfony\AI\Platform\Message\Message;

/**
 * Processes JSON-structured prompts for AI agents.
 *
 * @author Symfony AI Team
 */
final readonly class JsonPromptInputProcessor implements InputProcessorInterface
{
    /**
     * @param array<string, mixed> $jsonPrompt The JSON structure to use as the prompt
     */
    public function __construct(
        private array $jsonPrompt,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function processInput(Input $input): void
    {
        $messages = $input->messages;

        if (null !== $messages->getSystemMessage()) {
            $this->logger->debug('Skipping JSON prompt injection since MessageBag already contains a system message.');

            return;
        }

        // Convert the JSON structure to a formatted string for the system message
        $message = json_encode($this->jsonPrompt, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        $this->logger->debug('Injecting JSON prompt as system message', ['prompt' => $this->jsonPrompt]);

        $input->messages = $messages->prepend(Message::forSystem($message));
    }
}