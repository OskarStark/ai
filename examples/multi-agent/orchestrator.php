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
use Symfony\AI\Agent\Toolbox\Tool\Agent as AgentTool;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

require_once dirname(__DIR__).'/bootstrap.php';

/**
 * Multi-Agent Orchestrator Example
 * 
 * This example demonstrates an orchestrated workflow pattern where a central
 * orchestrator agent coordinates and delegates tasks to specialized agents.
 * Each agent has specific capabilities and expertise areas.
 */

// Initialize platform
$platform = PlatformFactory::create(env('OPENAI_API_KEY'), http_client());

// Create specialized agents with distinct system prompts
$researchAgent = new Agent(
    $platform,
    new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.1]),
    logger: logger()
);

$writingAgent = new Agent(
    $platform,
    new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.7]),
    logger: logger()
);

$reviewAgent = new Agent(
    $platform,
    new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.2]),
    logger: logger()
);

// Create orchestrator agent with access to specialized agents as tools
$orchestratorModel = new Gpt(Gpt::GPT_4O_MINI, ['temperature' => 0.3]);
$toolbox = new Toolbox();
$toolbox->add('research_agent', new AgentTool($researchAgent));
$toolbox->add('writing_agent', new AgentTool($writingAgent));  
$toolbox->add('review_agent', new AgentTool($reviewAgent));

$orchestrator = new Agent(
    $platform,
    $orchestratorModel,
    outputProcessors: [$toolbox->getAgentProcessor()],
    logger: logger()
);

// Define system prompts for each agent
$researchSystemPrompt = 'You are a research specialist. Your role is to gather comprehensive, accurate information on topics. Provide well-sourced facts, data, and key insights. Focus on finding relevant information from multiple perspectives and present it in an organized manner.';

$writingSystemPrompt = 'You are a creative writing specialist. Your role is to transform research and information into engaging, well-structured content. Write clearly, persuasively, and adapt your tone to the intended audience. Focus on storytelling, flow, and making complex topics accessible.';

$reviewSystemPrompt = 'You are a quality assurance specialist. Your role is to review content for accuracy, clarity, completeness, and overall quality. Identify gaps, inconsistencies, or areas for improvement. Provide constructive feedback and suggestions for enhancement.';

$orchestratorSystemPrompt = 'You are an orchestrator agent that coordinates work between specialized agents to complete complex tasks. You have access to three specialized agents:

1. research_agent: Gathers comprehensive information and facts on topics
2. writing_agent: Creates engaging, well-structured content from information
3. review_agent: Reviews content for quality, accuracy, and completeness

Your workflow should typically follow this pattern:
1. Use research_agent to gather information on the topic
2. Use writing_agent to create content based on the research
3. Use review_agent to evaluate and provide feedback on the content
4. If needed, iterate by going back to research or writing based on review feedback

Always coordinate the work efficiently and provide clear, specific instructions to each agent. Summarize the final output and explain how each agent contributed to the result.';

// Example task: Create a comprehensive article about renewable energy
$userQuery = 'Create a comprehensive, engaging article about the future of renewable energy technology. The article should be informative, well-researched, and suitable for a general audience interested in environmental topics.';

echo "🤖 Multi-Agent Orchestrator Starting...\n";
echo "Task: {$userQuery}\n";
echo str_repeat("=", 80) . "\n\n";

// Step 1: Research phase
echo "🔍 Phase 1: Research\n";
$researchResult = $researchAgent->call(new MessageBag(
    Message::forSystem($researchSystemPrompt),
    Message::ofUser('Research the future of renewable energy technology. Focus on current trends, emerging technologies, market predictions, environmental impact, and key challenges. Provide comprehensive information suitable for creating an informative article.')
));

echo "Research completed:\n";
echo substr($researchResult->getContent(), 0, 200) . "...\n\n";

// Step 2: Writing phase
echo "✍️ Phase 2: Writing\n";
$writingResult = $writingAgent->call(new MessageBag(
    Message::forSystem($writingSystemPrompt),
    Message::ofUser("Based on this research data, write a comprehensive, engaging article about the future of renewable energy technology suitable for a general audience:\n\n" . $researchResult->getContent())
));

echo "Article drafted:\n";
echo substr($writingResult->getContent(), 0, 200) . "...\n\n";

// Step 3: Review phase
echo "🔍 Phase 3: Review\n";
$reviewResult = $reviewAgent->call(new MessageBag(
    Message::forSystem($reviewSystemPrompt),
    Message::ofUser("Review this article about renewable energy technology. Evaluate its accuracy, clarity, completeness, and overall quality. Provide specific feedback and suggestions for improvement:\n\n" . $writingResult->getContent())
));

echo "Review completed:\n";
echo substr($reviewResult->getContent(), 0, 200) . "...\n\n";

// Step 4: Orchestrator coordination and final output
echo "🎭 Phase 4: Orchestrator Coordination\n";
$orchestratorResult = $orchestrator->call(new MessageBag(
    Message::forSystem($orchestratorSystemPrompt),
    Message::ofUser("Task: {$userQuery}\n\nI have preliminary results from the specialized agents:\n\nResearch: {$researchResult->getContent()}\n\nArticle Draft: {$writingResult->getContent()}\n\nReview Feedback: {$reviewResult->getContent()}\n\nPlease coordinate any additional work needed and provide the final output.")
));

echo "Final coordinated result:\n";
echo $orchestratorResult->getContent() . "\n\n";

echo str_repeat("=", 80) . "\n";
echo "✅ Multi-Agent Orchestration Complete!\n";
echo "The task was completed through coordinated efforts of:\n";
echo "- Research Agent: Gathered comprehensive information\n";
echo "- Writing Agent: Created engaging content\n";
echo "- Review Agent: Provided quality assurance\n";
echo "- Orchestrator: Coordinated the workflow and delivered final output\n";