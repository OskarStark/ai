<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\DriverManager;
use Symfony\AI\Embedder\OpenAi\Embedder;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Bridge\Dbal\Store;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Uid\Uuid;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

// Example using DBAL Store with automatic platform detection
// This example demonstrates how the DBAL store automatically adapts
// to different database platforms (MariaDB, PostgreSQL, etc.)

// Configure your database connection
// For MariaDB:
$connectionParams = [
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'test',
    'user' => 'root',
    'password' => '',
    'serverVersion' => '11.7.0-MariaDB',
];

// For PostgreSQL (uncomment to use):
// $connectionParams = [
//     'driver' => 'pdo_pgsql',
//     'host' => 'localhost',
//     'port' => 5432,
//     'dbname' => 'test',
//     'user' => 'postgres',
//     'password' => 'postgres',
// ];

// Create DBAL connection
$connection = DriverManager::getConnection($connectionParams);

// Create the DBAL store - it will automatically detect the database platform
$store = new Store($connection, 'embeddings');

// Initialize the store (creates table and indexes)
try {
    $store->initialize(['dimensions' => 1536]); // OpenAI embeddings are 1536 dimensions
    echo "Store initialized successfully!\n";
} catch (\Exception $e) {
    echo "Store initialization failed: ".$e->getMessage()."\n";
}

// Create embedder
$embedder = new Embedder(HttpClient::create([
    'auth_bearer' => $_ENV['OPENAI_API_KEY'] ?? throw new \Exception('Please set OPENAI_API_KEY'),
]));

// Sample documents
$documents = [
    'The capital of France is Paris.',
    'Berlin is the capital of Germany.',
    'London is the capital of the United Kingdom.',
    'Rome is the capital of Italy.',
    'Madrid is the capital of Spain.',
];

// Add documents to the store
echo "\nAdding documents to the store...\n";
foreach ($documents as $i => $content) {
    $embedding = $embedder->embed($content);
    
    $document = new VectorDocument(
        id: Uuid::v4(),
        vector: new Vector($embedding->getData()),
        metadata: new Metadata([
            'content' => $content,
            'index' => $i,
        ]),
    );
    
    $store->add($document);
    echo "Added: $content\n";
}

// Query the store
$query = 'What is the capital of France?';
echo "\nQuerying: $query\n";

$queryEmbedding = $embedder->embed($query);
$results = $store->query(new Vector($queryEmbedding->getData()), ['limit' => 3]);

echo "\nTop 3 results:\n";
foreach ($results as $i => $result) {
    $content = $result->metadata->get('content');
    $score = round($result->score, 4);
    echo sprintf("%d. %s (score: %s)\n", $i + 1, $content, $score);
}

// Demonstrate platform detection
$platform = $connection->getDatabasePlatform();
echo "\nDetected database platform: ".get_class($platform)."\n";