<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

require_once dirname(__DIR__).'/bootstrap.php';

/**
 * Multi-Agent Direct Collaboration Example
 * 
 * This example demonstrates a direct collaboration pattern where agents
 * work directly with each other through handoffs, passing context and
 * responsibility between specialized agents based on capability detection.
 */

// Initialize platform
$platform = PlatformFactory::create(env('OPENAI_API_KEY'), http_client());

/**
 * Specialized Agent Factory
 */
class SpecializedAgentFactory
{
    public function __construct(private $platform, private $logger)
    {
    }

    public function createResearchAgent(): Agent
    {
        return new Agent(
            $this->platform,
            new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.1]),
            logger: $this->logger
        );
    }

    public function createWritingAgent(): Agent
    {
        return new Agent(
            $this->platform,
            new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.7]),
            logger: $this->logger
        );
    }

    public function createReviewAgent(): Agent
    {
        return new Agent(
            $this->platform,
            new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.2]),
            logger: $this->logger
        );
    }

    public function createAnalysisAgent(): Agent
    {
        return new Agent(
            $this->platform,
            new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.3]),
            logger: $this->logger
        );
    }
}

/**
 * Multi-Agent Coordinator
 */
class MultiAgentCoordinator
{
    private array $agents = [];
    private array $agentCapabilities = [];

    public function __construct(private SpecializedAgentFactory $factory)
    {
        $this->initializeAgents();
        $this->defineCapabilities();
    }

    private function initializeAgents(): void
    {
        $this->agents = [
            'research' => $this->factory->createResearchAgent(),
            'writing' => $this->factory->createWritingAgent(),
            'review' => $this->factory->createReviewAgent(),
            'analysis' => $this->factory->createAnalysisAgent(),
        ];
    }

    private function defineCapabilities(): void
    {
        $this->agentCapabilities = [
            'research' => [
                'keywords' => ['research', 'investigate', 'find information', 'gather data', 'facts', 'sources'],
                'prompt' => 'You are a research specialist. Gather comprehensive, accurate information on the given topic. Provide well-sourced facts, data, and key insights. After completing your research, if the task requires content creation, hand off to the writing agent by saying "HANDOFF_TO_WRITING: [your research findings]".'
            ],
            'writing' => [
                'keywords' => ['write', 'create content', 'article', 'blog post', 'draft', 'compose'],
                'prompt' => 'You are a writing specialist. Transform information into engaging, well-structured content. Focus on storytelling, flow, and accessibility. After creating content, if quality review is needed, hand off by saying "HANDOFF_TO_REVIEW: [your content]".'
            ],
            'review' => [
                'keywords' => ['review', 'edit', 'proofread', 'quality check', 'improve', 'feedback'],
                'prompt' => 'You are a quality assurance specialist. Review content for accuracy, clarity, and completeness. Provide constructive feedback. If major revisions are needed, hand off back to writing by saying "HANDOFF_TO_WRITING: [revision suggestions]".'
            ],
            'analysis' => [
                'keywords' => ['analyze', 'evaluate', 'assess', 'examine', 'interpret', 'conclusions'],
                'prompt' => 'You are an analysis specialist. Examine data, identify patterns, and draw meaningful conclusions. Provide insights and recommendations based on your analysis.'
            ],
        ];
    }

    public function detectRequiredAgent(string $task): string
    {
        $taskLower = strtolower($task);
        
        foreach ($this->agentCapabilities as $agentType => $capabilities) {
            foreach ($capabilities['keywords'] as $keyword) {
                if (str_contains($taskLower, $keyword)) {
                    return $agentType;
                }
            }
        }

        // Default to research if no specific capability detected
        return 'research';
    }

    public function executeCollaborativeTask(string $task): string
    {
        echo "🤖 Starting collaborative task: {$task}\n";
        echo str_repeat("=", 80) . "\n\n";

        $currentAgent = $this->detectRequiredAgent($task);
        $context = $task;
        $history = [];
        $maxIterations = 5;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $iteration++;
            echo "🔄 Iteration {$iteration} - Current Agent: {$currentAgent}\n";

            $agent = $this->agents[$currentAgent];
            $systemPrompt = $this->agentCapabilities[$currentAgent]['prompt'];

            $result = $agent->call(new MessageBag(
                Message::forSystem($systemPrompt),
                Message::ofUser($context)
            ));

            $response = $result->getContent();
            $history[] = "{$currentAgent}: " . substr($response, 0, 100) . "...";

            echo "Agent {$currentAgent} response preview: " . substr($response, 0, 150) . "...\n\n";

            // Check for handoff
            if (preg_match('/HANDOFF_TO_(\w+):\s*(.+)/s', $response, $matches)) {
                $nextAgent = strtolower($matches[1]);
                $handoffContext = $matches[2];

                if (isset($this->agents[$nextAgent])) {
                    echo "🔄 Handoff detected: {$currentAgent} → {$nextAgent}\n";
                    $currentAgent = $nextAgent;
                    $context = "Previous work from {$currentAgent}: {$handoffContext}\n\nContinue with: {$task}";
                    continue;
                }
            }

            // If no handoff detected, task is complete
            echo "✅ Task completed by {$currentAgent} agent\n\n";
            echo "📋 Collaboration History:\n";
            foreach ($history as $entry) {
                echo "- {$entry}\n";
            }
            echo "\n";
            
            return $response;
        }

        return "Task completed after {$maxIterations} iterations. Final result from {$currentAgent} agent.";
    }
}

// Example usage
$factory = new SpecializedAgentFactory($platform, logger());
$coordinator = new MultiAgentCoordinator($factory);

// Example 1: Research task that should hand off to writing
echo "=== Example 1: Research → Writing Handoff ===\n";
$result1 = $coordinator->executeCollaborativeTask(
    "Research the benefits of electric vehicles and create an informative article about them"
);
echo "Final Result 1:\n{$result1}\n\n";

// Example 2: Writing task that should hand off to review
echo "=== Example 2: Writing → Review Handoff ===\n";
$result2 = $coordinator->executeCollaborativeTask(
    "Write a blog post about sustainable living tips and ensure it's properly reviewed for quality"
);
echo "Final Result 2:\n{$result2}\n\n";

// Example 3: Analysis task
echo "=== Example 3: Analysis Task ===\n";
$result3 = $coordinator->executeCollaborativeTask(
    "Analyze the current trends in renewable energy adoption and provide insights"
);
echo "Final Result 3:\n{$result3}\n\n";

echo str_repeat("=", 80) . "\n";
echo "✅ Multi-Agent Direct Collaboration Examples Complete!\n";
echo "This demonstrates:\n";
echo "- Capability-based agent detection\n";
echo "- Context handoffs between agents\n";
echo "- Autonomous collaboration without central orchestration\n";