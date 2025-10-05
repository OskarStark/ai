<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Tests\Toolbox;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Input;
use Symfony\AI\Agent\Output;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\ToolCallMessage;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;

class AgentProcessorTest extends TestCase
{
    public function testProcessInputWithoutRegisteredToolsWillResultInNoOptionChange()
    {
        $toolbox = $this->createStub(ToolboxInterface::class);
        $toolbox->method('getTools')->willReturn([]);

        $processor = new AgentProcessor($toolbox);
        $input = new Input('gpt-4', new MessageBag());

        $processor->processInput($input);

        $this->assertSame([], $input->getOptions());
    }

    public function testProcessInputWithRegisteredToolsWillResultInOptionChange()
    {
        $toolbox = $this->createStub(ToolboxInterface::class);
        $tool1 = new Tool(new ExecutionReference('ClassTool1', 'method1'), 'tool1', 'description1', null);
        $tool2 = new Tool(new ExecutionReference('ClassTool2', 'method1'), 'tool2', 'description2', null);
        $toolbox->method('getTools')->willReturn([$tool1, $tool2]);

        $processor = new AgentProcessor($toolbox);
        $input = new Input('gpt-4', new MessageBag());

        $processor->processInput($input);

        $this->assertSame(['tools' => [$tool1, $tool2]], $input->getOptions());
    }

    public function testProcessInputWithRegisteredToolsButToolOverride()
    {
        $toolbox = $this->createStub(ToolboxInterface::class);
        $tool1 = new Tool(new ExecutionReference('ClassTool1', 'method1'), 'tool1', 'description1', null);
        $tool2 = new Tool(new ExecutionReference('ClassTool2', 'method1'), 'tool2', 'description2', null);
        $toolbox->method('getTools')->willReturn([$tool1, $tool2]);

        $processor = new AgentProcessor($toolbox);
        $input = new Input('gpt-4', new MessageBag(), ['tools' => ['tool2']]);

        $processor->processInput($input);

        $this->assertSame(['tools' => [$tool2]], $input->getOptions());
    }

    public function testProcessOutputWithToolCallResponseKeepingMessages()
    {
        $toolbox = $this->createMock(ToolboxInterface::class);
        $toolbox->expects($this->once())->method('execute')->willReturn('Test response');

        $messageBag = new MessageBag();

        $result = new ToolCallResult(new ToolCall('id1', 'tool1', ['arg1' => 'value1']));

        $agent = $this->createStub(AgentInterface::class);

        $processor = new AgentProcessor($toolbox, keepToolMessages: true);
        $processor->setAgent($agent);

        $output = new Output('gpt-4', $result, $messageBag);

        $processor->processOutput($output);

        $this->assertCount(2, $messageBag);
        $this->assertInstanceOf(AssistantMessage::class, $messageBag->getMessages()[0]);
        $this->assertInstanceOf(ToolCallMessage::class, $messageBag->getMessages()[1]);
    }

    public function testProcessOutputWithToolCallResponseForgettingMessages()
    {
        $toolbox = $this->createMock(ToolboxInterface::class);
        $toolbox->expects($this->once())->method('execute')->willReturn('Test response');

        $messageBag = new MessageBag();

        $result = new ToolCallResult(new ToolCall('id1', 'tool1', ['arg1' => 'value1']));

        $agent = $this->createStub(AgentInterface::class);

        $processor = new AgentProcessor($toolbox, keepToolMessages: false);
        $processor->setAgent($agent);

        $output = new Output('gpt-4', $result, $messageBag);

        $processor->processOutput($output);

        $this->assertCount(0, $messageBag);
    }
}
