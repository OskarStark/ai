<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Tests\InputProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\AI\Agent\Input;
use Symfony\AI\Agent\InputProcessor\JsonPromptInputProcessor;
use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;

#[CoversClass(JsonPromptInputProcessor::class)]
class JsonPromptInputProcessorTest extends TestCase
{
    public function testProcessInputWithSimpleJsonPrompt(): void
    {
        $jsonPrompt = [
            'role' => 'assistant',
            'task' => 'Help with coding',
            'style' => 'concise',
        ];

        $processor = new JsonPromptInputProcessor($jsonPrompt);
        
        $messages = new MessageBag(
            Message::ofUser('Hello')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);

        $systemMessage = $input->messages->getSystemMessage();
        $this->assertNotNull($systemMessage);
        
        $decodedPrompt = json_decode($systemMessage->content, true);
        $this->assertEquals($jsonPrompt, $decodedPrompt);
    }

    public function testProcessInputWithComplexNestedJsonPrompt(): void
    {
        $jsonPrompt = [
            'system' => [
                'role' => 'expert developer',
                'languages' => ['PHP', 'JavaScript', 'Python'],
                'expertise' => [
                    'backend' => ['Symfony', 'Laravel'],
                    'frontend' => ['React', 'Vue.js'],
                ],
            ],
            'instructions' => [
                'style' => 'professional',
                'format' => 'markdown',
                'examples' => true,
            ],
            'constraints' => [
                'max_response_length' => 500,
                'include_code_examples' => true,
            ],
        ];

        $processor = new JsonPromptInputProcessor($jsonPrompt);
        
        $messages = new MessageBag(
            Message::ofUser('How do I create a REST API?')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);

        $systemMessage = $input->messages->getSystemMessage();
        $this->assertNotNull($systemMessage);
        
        // Check that the JSON is properly formatted
        $this->assertJson($systemMessage->content);
        
        // Verify the structure is preserved
        $decodedPrompt = json_decode($systemMessage->content, true);
        $this->assertEquals($jsonPrompt, $decodedPrompt);
    }

    public function testSkipsProcessingWhenSystemMessageExists(): void
    {
        $jsonPrompt = ['role' => 'assistant'];
        $logger = $this->createMock(NullLogger::class);
        
        $logger->expects($this->once())
            ->method('debug')
            ->with('Skipping JSON prompt injection since MessageBag already contains a system message.');

        $processor = new JsonPromptInputProcessor($jsonPrompt, null, $logger);
        
        $messages = new MessageBag(
            Message::forSystem('Existing system message'),
            Message::ofUser('Hello')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);

        // System message should remain unchanged
        $this->assertEquals('Existing system message', $input->messages->getSystemMessage()->content);
    }

    public function testProcessInputWithUnicodeContent(): void
    {
        $jsonPrompt = [
            'role' => 'translator',
            'languages' => ['English', 'Français', '日本語', '中文'],
            'special_chars' => '€ £ ¥ © ® ™',
            'emoji' => '😀 🎉 🚀',
        ];

        $processor = new JsonPromptInputProcessor($jsonPrompt);
        
        $messages = new MessageBag(
            Message::ofUser('Translate something')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);

        $systemMessage = $input->messages->getSystemMessage();
        $this->assertNotNull($systemMessage);
        
        // Check that Unicode characters are preserved
        $this->assertStringContainsString('日本語', $systemMessage->content);
        $this->assertStringContainsString('€', $systemMessage->content);
        $this->assertStringContainsString('😀', $systemMessage->content);
        
        // Verify the structure is preserved after decoding
        $decodedPrompt = json_decode($systemMessage->content, true);
        $this->assertEquals($jsonPrompt, $decodedPrompt);
    }

    public function testLoggingOnSuccessfulInjection(): void
    {
        $jsonPrompt = ['task' => 'test'];
        $logger = $this->createMock(NullLogger::class);
        
        $logger->expects($this->once())
            ->method('debug')
            ->with('Injecting JSON prompt as system message', ['prompt' => $jsonPrompt]);

        $processor = new JsonPromptInputProcessor($jsonPrompt, null, $logger);
        
        $messages = new MessageBag(
            Message::ofUser('Test')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);
    }

    public function testProcessInputWithToolbox(): void
    {
        $jsonPrompt = [
            'role' => 'assistant',
            'task' => 'Help with tasks',
        ];

        // Mock toolbox with tools
        $toolbox = $this->createMock(ToolboxInterface::class);
        
        $tool1 = new Tool(
            reference: new ExecutionReference('SearchTool'),
            name: 'search_tool',
            description: 'Search for information',
            parameters: []
        );
        
        $tool2 = new Tool(
            reference: new ExecutionReference('CalculateTool'),
            name: 'calculate_tool',
            description: 'Perform calculations',
            parameters: []
        );
        
        $toolbox->expects($this->exactly(2))
            ->method('getTools')
            ->willReturn([$tool1, $tool2]);

        $processor = new JsonPromptInputProcessor($jsonPrompt, $toolbox);
        
        $messages = new MessageBag(
            Message::ofUser('Hello')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);

        $systemMessage = $input->messages->getSystemMessage();
        $this->assertNotNull($systemMessage);
        
        // Verify the JSON contains tools
        $decodedPrompt = json_decode($systemMessage->content, true);
        $this->assertArrayHasKey('available_tools', $decodedPrompt);
        $this->assertCount(2, $decodedPrompt['available_tools']);
        
        // Verify tool structure
        $this->assertEquals([
            ['name' => 'search_tool', 'description' => 'Search for information'],
            ['name' => 'calculate_tool', 'description' => 'Perform calculations'],
        ], $decodedPrompt['available_tools']);
        
        // Verify original prompt data is preserved
        $this->assertEquals('assistant', $decodedPrompt['role']);
        $this->assertEquals('Help with tasks', $decodedPrompt['task']);
    }

    public function testProcessInputWithEmptyToolbox(): void
    {
        $jsonPrompt = [
            'role' => 'assistant',
            'task' => 'Help with tasks',
        ];

        // Mock toolbox with no tools
        $toolbox = $this->createMock(ToolboxInterface::class);
        $toolbox->expects($this->once())
            ->method('getTools')
            ->willReturn([]);

        $processor = new JsonPromptInputProcessor($jsonPrompt, $toolbox);
        
        $messages = new MessageBag(
            Message::ofUser('Test')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);

        $systemMessage = $input->messages->getSystemMessage();
        $this->assertNotNull($systemMessage);
        
        // Verify no tools are added when toolbox is empty
        $decodedPrompt = json_decode($systemMessage->content, true);
        $this->assertArrayNotHasKey('available_tools', $decodedPrompt);
        
        // Verify original prompt data is preserved
        $this->assertEquals($jsonPrompt, $decodedPrompt);
    }

    public function testProcessInputWithoutToolbox(): void
    {
        $jsonPrompt = [
            'role' => 'assistant',
            'task' => 'Help with tasks',
        ];

        // No toolbox provided
        $processor = new JsonPromptInputProcessor($jsonPrompt);
        
        $messages = new MessageBag(
            Message::ofUser('Test')
        );
        $input = new Input(new Gpt(), $messages, []);

        $processor->processInput($input);

        $systemMessage = $input->messages->getSystemMessage();
        $this->assertNotNull($systemMessage);
        
        // Verify no tools are added when no toolbox is provided
        $decodedPrompt = json_decode($systemMessage->content, true);
        $this->assertArrayNotHasKey('available_tools', $decodedPrompt);
        
        // Verify original prompt data is preserved
        $this->assertEquals($jsonPrompt, $decodedPrompt);
    }
}