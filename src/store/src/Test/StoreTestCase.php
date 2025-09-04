<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\StoreInterface;

/**
 * A test case to ease testing a Store implementation.
 *
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
abstract class StoreTestCase extends TestCase
{
    /**
     * Creates a store instance for testing.
     *
     * @param array<string, mixed> $options Optional configuration options for the store
     */
    abstract public static function createStore(array $options = []): StoreInterface;

    /**
     * @return iterable<array{0: VectorDocument[], 1: Vector, 2: array<string, mixed>, 3: int, 4?: StoreInterface}>
     */
    abstract public static function queryProvider(): iterable;

    /**
     * @return iterable<array{0: VectorDocument[], 1?: StoreInterface}>
     */
    abstract public static function documentsProvider(): iterable;

    /**
     * @dataProvider documentsProvider
     */
    #[DataProvider('documentsProvider')]
    public function testAddDocuments(array $documents, ?StoreInterface $store = null)
    {
        $store ??= static::createStore();

        $store->add(...$documents);

        if (empty($documents)) {
            $this->assertTrue(true); // No exception means success

            return;
        }

        // Verify documents were added by querying with first document's vector
        $firstDocument = reset($documents);
        $results = $store->query($firstDocument->vector, ['maxItems' => \count($documents)]);

        // Check that we got results
        $this->assertNotEmpty($results);

        // Check that the first document is in the results (it should be the closest match to itself)
        $foundIds = array_map(fn (VectorDocument $doc) => $doc->id->toRfc4122(), $results);
        $this->assertContains($firstDocument->id->toRfc4122(), $foundIds);
    }

    /**
     * @dataProvider queryProvider
     */
    #[DataProvider('queryProvider')]
    public function testQuery(array $documents, Vector $queryVector, array $options, int $expectedCount, ?StoreInterface $store = null)
    {
        $store ??= static::createStore();

        // Add documents to the store
        if (!empty($documents)) {
            $store->add(...$documents);
        }

        // Query the store
        $results = $store->query($queryVector, $options);

        // Assert expected count
        $this->assertCount($expectedCount, $results);

        // Verify all results are VectorDocument instances
        foreach ($results as $result) {
            $this->assertInstanceOf(VectorDocument::class, $result);
        }

        // If we have results, verify they're ordered by score (ascending - closest first)
        if (\count($results) > 1) {
            $previousScore = null;
            foreach ($results as $result) {
                if (null !== $result->score) {
                    if (null !== $previousScore) {
                        $this->assertGreaterThanOrEqual($previousScore, $result->score, 'Results should be ordered by score (ascending)');
                    }
                    $previousScore = $result->score;
                }
            }
        }
    }

    public function testEmptyQuery()
    {
        $store = static::createStore();

        // Query empty store
        $results = $store->query(new Vector([0.1, 0.2, 0.3]));

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testQueryWithMaxItems()
    {
        $store = static::createStore();

        // Add multiple documents
        $documents = [];
        for ($i = 0; $i < 10; ++$i) {
            $documents[] = new VectorDocument(
                \Symfony\Component\Uid\Uuid::v4(),
                new Vector([0.1 * $i, 0.2 * $i, 0.3 * $i])
            );
        }
        $store->add(...$documents);

        // Query with maxItems
        $results = $store->query(new Vector([0.1, 0.2, 0.3]), ['maxItems' => 3]);

        $this->assertLessThanOrEqual(3, \count($results));
    }

    public function testAddSingleDocument()
    {
        $store = static::createStore();

        $document = new VectorDocument(
            \Symfony\Component\Uid\Uuid::v4(),
            new Vector([0.1, 0.2, 0.3])
        );

        $store->add($document);

        $results = $store->query($document->vector, ['maxItems' => 1]);

        $this->assertCount(1, $results);
        $this->assertEquals($document->id->toRfc4122(), $results[0]->id->toRfc4122());
    }

    public function testAddMultipleDocuments()
    {
        $store = static::createStore();

        $doc1 = new VectorDocument(
            \Symfony\Component\Uid\Uuid::v4(),
            new Vector([0.1, 0.2, 0.3])
        );
        $doc2 = new VectorDocument(
            \Symfony\Component\Uid\Uuid::v4(),
            new Vector([0.4, 0.5, 0.6])
        );

        $store->add($doc1, $doc2);

        $results = $store->query(new Vector([0.25, 0.35, 0.45]), ['maxItems' => 2]);

        $this->assertCount(2, $results);
    }

    protected function assertVectorEquals(Vector $expected, Vector $actual, string $message = '')
    {
        $this->assertEquals($expected->getData(), $actual->getData(), $message);
    }

    protected function assertDocumentEquals(VectorDocument $expected, VectorDocument $actual, string $message = '')
    {
        $this->assertEquals($expected->id->toRfc4122(), $actual->id->toRfc4122(), $message.' - Document IDs do not match');
        $this->assertVectorEquals($expected->vector, $actual->vector, $message.' - Document vectors do not match');
        $this->assertEquals($expected->metadata->getArrayCopy(), $actual->metadata->getArrayCopy(), $message.' - Document metadata does not match');
    }
}

