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
use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Tool\Tool;

/**
 * Processes JSON-structured prompts for AI agents.
 *
 * @author Symfony AI Team
 */
final readonly class JsonPromptInputProcessor implements InputProcessorInterface
{
    /**
     * @param array<string, mixed>  $jsonPrompt The JSON structure to use as the prompt
     * @param ToolboxInterface|null $toolbox    The toolbox to be used to append the tool definitions to the JSON prompt
     */
    public function __construct(
        private array $jsonPrompt,
        private ?ToolboxInterface $toolbox = null,
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

        $promptData = $this->jsonPrompt;

        // Append tool definitions if toolbox is provided and has tools
        if ($this->toolbox instanceof ToolboxInterface && [] !== $this->toolbox->getTools()) {
            $this->logger->debug('Appending tool definitions to JSON prompt.');

            $tools = array_map(
                fn (Tool $tool) => [
                    'name' => $tool->name,
                    'description' => $tool->description,
                ],
                $this->toolbox->getTools()
            );

            $promptData['available_tools'] = $tools;
        }

        // Convert the JSON structure to a formatted string for the system message
        $message = json_encode($promptData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        $this->logger->debug('Injecting JSON prompt as system message', ['prompt' => $promptData]);

        $input->messages = $messages->prepend(Message::forSystem($message));
    }
}