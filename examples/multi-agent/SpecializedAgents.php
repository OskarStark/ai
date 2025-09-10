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
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use Psr\Log\LoggerInterface;

/**
 * Base class for specialized agents with common functionality
 */
abstract class SpecializedAgent
{
    protected Agent $agent;
    protected string $specialty;
    protected array $capabilities = [];

    public function __construct(
        PlatformInterface $platform,
        LoggerInterface $logger,
        float $temperature = 0.5
    ) {
        $this->agent = new Agent(
            $platform,
            new Gpt(Gpt::GPT_4O_MINI, ['temperature' => $temperature]),
            logger: $logger
        );
        $this->specialty = $this->getSpecialty();
        $this->capabilities = $this->getCapabilities();
    }

    abstract protected function getSpecialty(): string;
    abstract protected function getCapabilities(): array;
    abstract protected function getSystemPrompt(): string;

    public function execute(string $task, array $context = []): string
    {
        $contextString = '';
        if (!empty($context)) {
            $contextString = "\n\nContext from previous agents:\n" . implode("\n", $context);
        }

        $result = $this->agent->call(new MessageBag(
            Message::forSystem($this->getSystemPrompt()),
            Message::ofUser($task . $contextString)
        ));

        return $result->getContent();
    }

    public function canHandle(string $task): bool
    {
        $taskLower = strtolower($task);
        foreach ($this->capabilities as $capability) {
            if (str_contains($taskLower, strtolower($capability))) {
                return true;
            }
        }
        return false;
    }

    public function getSpecialtyInfo(): array
    {
        return [
            'specialty' => $this->specialty,
            'capabilities' => $this->capabilities,
        ];
    }
}

/**
 * Research specialist agent for information gathering
 */
class ResearchAgent extends SpecializedAgent
{
    public function __construct(PlatformInterface $platform, LoggerInterface $logger)
    {
        parent::__construct($platform, $logger, 0.1); // Low temperature for factual accuracy
    }

    protected function getSpecialty(): string
    {
        return 'Research and Information Gathering';
    }

    protected function getCapabilities(): array
    {
        return [
            'research', 'investigate', 'find information', 'gather data', 
            'facts', 'sources', 'analyze data', 'market research', 'surveys'
        ];
    }

    protected function getSystemPrompt(): string
    {
        return 'You are a research specialist with expertise in gathering comprehensive, accurate information on any topic. Your responsibilities include:

1. Conducting thorough research using multiple perspectives
2. Providing well-sourced facts, data, and key insights
3. Organizing information in a logical, structured manner
4. Identifying reliable sources and data points
5. Highlighting key trends, statistics, and findings

Focus on accuracy, completeness, and presenting information that can be easily used by other specialists. When your research is complete and the task involves content creation, indicate this by ending with: "RESEARCH_COMPLETE: Ready for content creation based on findings."';
    }
}

/**
 * Writing specialist agent for content creation
 */
class WritingAgent extends SpecializedAgent
{
    public function __construct(PlatformInterface $platform, LoggerInterface $logger)
    {
        parent::__construct($platform, $logger, 0.7); // Higher temperature for creativity
    }

    protected function getSpecialty(): string
    {
        return 'Content Creation and Writing';
    }

    protected function getCapabilities(): array
    {
        return [
            'write', 'create content', 'article', 'blog post', 'draft', 
            'compose', 'storytelling', 'copywriting', 'technical writing'
        ];
    }

    protected function getSystemPrompt(): string
    {
        return 'You are a creative writing specialist with expertise in transforming information into engaging, well-structured content. Your responsibilities include:

1. Creating compelling, reader-friendly content from research data
2. Adapting tone and style to target audience
3. Ensuring proper flow, structure, and readability
4. Making complex topics accessible and interesting
5. Incorporating storytelling elements where appropriate

Focus on clarity, engagement, and maintaining accuracy while making content compelling. When your content needs quality review, end with: "CONTENT_COMPLETE: Ready for quality review and feedback."';
    }
}

/**
 * Review specialist agent for quality assurance
 */
class ReviewAgent extends SpecializedAgent
{
    public function __construct(PlatformInterface $platform, LoggerInterface $logger)
    {
        parent::__construct($platform, $logger, 0.2); // Low temperature for consistent evaluation
    }

    protected function getSpecialty(): string
    {
        return 'Quality Assurance and Review';
    }

    protected function getCapabilities(): array
    {
        return [
            'review', 'edit', 'proofread', 'quality check', 'improve', 
            'feedback', 'critique', 'evaluate', 'assess quality'
        ];
    }

    protected function getSystemPrompt(): string
    {
        return 'You are a quality assurance specialist with expertise in reviewing content for accuracy, clarity, and overall quality. Your responsibilities include:

1. Evaluating content for accuracy and factual correctness
2. Checking clarity, readability, and flow
3. Identifying gaps, inconsistencies, or areas for improvement
4. Providing constructive, specific feedback
5. Ensuring content meets quality standards

Focus on thorough evaluation and providing actionable feedback. If significant revisions are needed, end with: "REVISION_NEEDED: [specific suggestions]". If content is ready, end with: "QUALITY_APPROVED: Content meets standards."';
    }
}

/**
 * Analysis specialist agent for data interpretation
 */
class AnalysisAgent extends SpecializedAgent
{
    public function __construct(PlatformInterface $platform, LoggerInterface $logger)
    {
        parent::__construct($platform, $logger, 0.3); // Moderate temperature for balanced analysis
    }

    protected function getSpecialty(): string
    {
        return 'Data Analysis and Insights';
    }

    protected function getCapabilities(): array
    {
        return [
            'analyze', 'evaluate', 'assess', 'examine', 'interpret', 
            'conclusions', 'insights', 'patterns', 'trends', 'statistics'
        ];
    }

    protected function getSystemPrompt(): string
    {
        return 'You are an analysis specialist with expertise in examining data, identifying patterns, and drawing meaningful conclusions. Your responsibilities include:

1. Analyzing complex data sets and information
2. Identifying patterns, trends, and correlations  
3. Drawing evidence-based conclusions
4. Providing actionable insights and recommendations
5. Presenting findings in a clear, structured manner

Focus on objective analysis, evidence-based reasoning, and practical insights that can inform decision-making.';
    }
}

/**
 * Multi-Agent System Manager
 */
class MultiAgentSystem
{
    private array $agents = [];
    private array $executionHistory = [];

    public function __construct(PlatformInterface $platform, LoggerInterface $logger)
    {
        $this->agents = [
            'research' => new ResearchAgent($platform, $logger),
            'writing' => new WritingAgent($platform, $logger),
            'review' => new ReviewAgent($platform, $logger),
            'analysis' => new AnalysisAgent($platform, $logger),
        ];
    }

    public function getAvailableAgents(): array
    {
        $agentInfo = [];
        foreach ($this->agents as $name => $agent) {
            $agentInfo[$name] = $agent->getSpecialtyInfo();
        }
        return $agentInfo;
    }

    public function findBestAgent(string $task): string
    {
        foreach ($this->agents as $name => $agent) {
            if ($agent->canHandle($task)) {
                return $name;
            }
        }
        return 'research'; // Default fallback
    }

    public function executeTask(string $agentName, string $task, array $context = []): string
    {
        if (!isset($this->agents[$agentName])) {
            throw new InvalidArgumentException("Agent '{$agentName}' not found.");
        }

        $result = $this->agents[$agentName]->execute($task, $context);
        
        $this->executionHistory[] = [
            'agent' => $agentName,
            'task' => $task,
            'result_preview' => substr($result, 0, 100) . '...',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $result;
    }

    public function getExecutionHistory(): array
    {
        return $this->executionHistory;
    }

    public function clearHistory(): void
    {
        $this->executionHistory = [];
    }
}