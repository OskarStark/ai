<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;

require_once dirname(__DIR__).'/bootstrap.php';
require_once __DIR__.'/SpecializedAgents.php';

/**
 * Comprehensive Multi-Agent Usage Example
 * 
 * This example demonstrates practical usage of the multi-agent system
 * with various real-world scenarios and task patterns.
 */

// Initialize platform and multi-agent system
$platform = PlatformFactory::create(env('OPENAI_API_KEY'), http_client());
$multiAgentSystem = new MultiAgentSystem($platform, logger());

echo "🤖 Multi-Agent System Usage Examples\n";
echo str_repeat("=", 80) . "\n\n";

// Display available agents
echo "📋 Available Agents:\n";
foreach ($multiAgentSystem->getAvailableAgents() as $name => $info) {
    echo "- {$name}: {$info['specialty']}\n";
    echo "  Capabilities: " . implode(', ', array_slice($info['capabilities'], 0, 5)) . "...\n\n";
}

/**
 * Example 1: Sequential Multi-Agent Workflow
 */
echo "=== Example 1: Sequential Workflow (Research → Write → Review) ===\n";

$topic = "artificial intelligence in healthcare";

// Step 1: Research
echo "🔍 Step 1: Research Phase\n";
$researchResult = $multiAgentSystem->executeTask(
    'research',
    "Research the latest developments in {$topic}. Focus on current applications, benefits, challenges, and future prospects."
);
echo "Research completed: " . substr($researchResult, 0, 200) . "...\n\n";

// Step 2: Writing
echo "✍️ Step 2: Writing Phase\n";
$writingResult = $multiAgentSystem->executeTask(
    'writing',
    "Create an engaging article about {$topic} suitable for healthcare professionals.",
    ["Research findings: " . $researchResult]
);
echo "Article created: " . substr($writingResult, 0, 200) . "...\n\n";

// Step 3: Review
echo "🔍 Step 3: Review Phase\n";
$reviewResult = $multiAgentSystem->executeTask(
    'review',
    "Review this article about {$topic} for accuracy, clarity, and professional suitability.",
    ["Article to review: " . $writingResult]
);
echo "Review completed: " . substr($reviewResult, 0, 200) . "...\n\n";

/**
 * Example 2: Automatic Agent Selection
 */
echo "=== Example 2: Automatic Agent Selection ===\n";

$tasks = [
    "Analyze market trends in renewable energy sector",
    "Write a technical blog post about machine learning algorithms", 
    "Research competitor pricing strategies",
    "Review this marketing copy for clarity and effectiveness"
];

foreach ($tasks as $task) {
    $bestAgent = $multiAgentSystem->findBestAgent($task);
    echo "📝 Task: {$task}\n";
    echo "🎯 Selected Agent: {$bestAgent}\n";
    
    $result = $multiAgentSystem->executeTask($bestAgent, $task);
    echo "✅ Result preview: " . substr($result, 0, 150) . "...\n\n";
}

/**
 * Example 3: Complex Multi-Step Analysis
 */
echo "=== Example 3: Complex Analysis Workflow ===\n";

$businessScenario = "launching a new eco-friendly product line";

// Analysis phase
echo "📊 Analysis Phase\n";
$analysisResult = $multiAgentSystem->executeTask(
    'analysis',
    "Analyze the market opportunity and key success factors for {$businessScenario}. Consider market size, competition, customer needs, and potential challenges."
);
echo "Analysis completed: " . substr($analysisResult, 0, 200) . "...\n\n";

// Research phase (building on analysis)
echo "🔍 Research Phase\n";
$detailedResearch = $multiAgentSystem->executeTask(
    'research',
    "Based on the initial analysis, research specific data and case studies for {$businessScenario}.",
    ["Initial analysis: " . $analysisResult]
);
echo "Detailed research completed: " . substr($detailedResearch, 0, 200) . "...\n\n";

// Strategic document creation
echo "📋 Strategy Document Creation\n";
$strategyDocument = $multiAgentSystem->executeTask(
    'writing',
    "Create a comprehensive strategy document for {$businessScenario} based on the analysis and research.",
    [
        "Market analysis: " . $analysisResult,
        "Research findings: " . $detailedResearch
    ]
);
echo "Strategy document created: " . substr($strategyDocument, 0, 200) . "...\n\n";

/**
 * Example 4: Iterative Improvement Workflow
 */
echo "=== Example 4: Iterative Improvement ===\n";

$contentTopic = "cybersecurity best practices for small businesses";

// Initial draft
$draft = $multiAgentSystem->executeTask(
    'writing',
    "Write a comprehensive guide about {$contentTopic}"
);

// Review and feedback
$feedback = $multiAgentSystem->executeTask(
    'review',
    "Review this guide and provide specific improvement suggestions",
    ["Guide to review: " . $draft]
);

// Improved version
$improvedDraft = $multiAgentSystem->executeTask(
    'writing',
    "Revise the guide based on the review feedback",
    [
        "Original guide: " . $draft,
        "Review feedback: " . $feedback
    ]
);

echo "📝 Iterative improvement completed\n";
echo "Original draft length: " . strlen($draft) . " characters\n";
echo "Improved draft length: " . strlen($improvedDraft) . " characters\n\n";

/**
 * Example 5: Parallel Processing Simulation
 */
echo "=== Example 5: Parallel Processing Concept ===\n";

$parallelTasks = [
    ['agent' => 'research', 'task' => 'Research sustainable packaging materials'],
    ['agent' => 'analysis', 'task' => 'Analyze consumer preferences for eco-friendly products'],
    ['agent' => 'writing', 'task' => 'Draft marketing messages for environmentally conscious consumers'],
];

echo "🔄 Processing parallel tasks:\n";
$parallelResults = [];

foreach ($parallelTasks as $taskInfo) {
    echo "- {$taskInfo['agent']}: {$taskInfo['task']}\n";
    $parallelResults[] = $multiAgentSystem->executeTask($taskInfo['agent'], $taskInfo['task']);
}

// Synthesis of parallel results
echo "\n📋 Synthesis Phase\n";
$synthesisResult = $multiAgentSystem->executeTask(
    'analysis',
    "Synthesize insights from parallel research and create actionable recommendations",
    $parallelResults
);
echo "Synthesis completed: " . substr($synthesisResult, 0, 200) . "...\n\n";

/**
 * Display execution history and statistics
 */
echo "=== Execution Summary ===\n";
$history = $multiAgentSystem->getExecutionHistory();
$agentUsage = [];

foreach ($history as $entry) {
    $agentUsage[$entry['agent']] = ($agentUsage[$entry['agent']] ?? 0) + 1;
}

echo "📊 Agent Usage Statistics:\n";
foreach ($agentUsage as $agent => $count) {
    echo "- {$agent}: {$count} tasks\n";
}

echo "\n🕒 Recent Execution History:\n";
foreach (array_slice($history, -5) as $entry) {
    echo "- [{$entry['timestamp']}] {$entry['agent']}: {$entry['result_preview']}\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Multi-Agent System Usage Examples Complete!\n\n";

echo "💡 Key Takeaways:\n";
echo "1. Sequential workflows enable complex task completion through agent collaboration\n";
echo "2. Automatic agent selection optimizes task assignment based on capabilities\n";
echo "3. Iterative improvement workflows enhance output quality through feedback loops\n";
echo "4. Context passing between agents maintains continuity across tasks\n";
echo "5. Parallel processing concepts can be simulated for complex project scenarios\n";