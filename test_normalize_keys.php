<?php

/*
 * Test case to explore how normalizeKeys(false) behaves
 * in Symfony Config Definition
 */

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

// Test 1: With normalizeKeys(false) - preserves case-sensitive keys
$treeBuilder1 = new TreeBuilder('test1');
$rootNode1 = $treeBuilder1->getRootNode();

$rootNode1
    ->children()
        ->arrayNode('azure')
            ->normalizeKeys(false)
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('api_key')->isRequired()->end()
                    ->scalarNode('base_url')->isRequired()->end()
                ->end()
            ->end()
        ->end()
    ->end();

// Test 2: Without normalizeKeys(false) - normalizes keys to lowercase
$treeBuilder2 = new TreeBuilder('test2');
$rootNode2 = $treeBuilder2->getRootNode();

$rootNode2
    ->children()
        ->arrayNode('azure')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('api_key')->isRequired()->end()
                    ->scalarNode('base_url')->isRequired()->end()
                ->end()
            ->end()
        ->end()
    ->end();

$processor = new Processor();

// Test data with mixed case keys
$config = [
    'azure' => [
        'GPT-4' => [
            'api_key' => 'key1',
            'base_url' => 'https://api1.example.com',
        ],
        'gpt-3.5-turbo' => [
            'api_key' => 'key2',
            'base_url' => 'https://api2.example.com',
        ],
        'Text-Embedding-Ada' => [
            'api_key' => 'key3',
            'base_url' => 'https://api3.example.com',
        ],
    ],
];

$config2 = [
    'azure' => [
        'GPT-4' => [
            'api_key' => 'key1',
            'base_url' => 'https://api1.example.com',
        ],
        'gpt-3.5-turbo' => [
            'api_key' => 'key2',
            'base_url' => 'https://api2.example.com',
        ],
        'Text-Embedding-Ada' => [
            'api_key' => 'key3',
            'base_url' => 'https://api3.example.com',
        ],
    ],
];

echo "=== Test 1: With normalizeKeys(false) ===\n";
echo "Preserves the original case of the keys\n\n";

try {
    $processedConfig1 = $processor->process($treeBuilder1->buildTree(), [$config]);
    echo "Result:\n";
    print_r($processedConfig1);
    
    echo "\nKeys in 'azure' array:\n";
    foreach (array_keys($processedConfig1['azure']) as $key) {
        echo "  - '$key'\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test 2: Without normalizeKeys(false) ===\n";
echo "Normalizes all keys to lowercase\n\n";

try {
    $processedConfig2 = $processor->process($treeBuilder2->buildTree(), [$config2]);
    echo "Result:\n";
    print_r($processedConfig2);
    
    echo "\nKeys in 'azure' array:\n";
    foreach (array_keys($processedConfig2['azure']) as $key) {
        echo "  - '$key'\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "normalizeKeys(false) is crucial when:\n";
echo "- You need to preserve the exact case of configuration keys\n";
echo "- Keys might contain uppercase letters (e.g., 'GPT-4', 'Text-Embedding-Ada')\n";
echo "- The keys are used as identifiers that are case-sensitive\n";
echo "\nIn the ai-bundle config, it's used for:\n";
echo "- Azure service names (which might be deployment names like 'GPT-4')\n";
echo "- Agent names (which could be case-sensitive identifiers)\n";
echo "- Store configurations (database names, collection names, etc.)\n";