<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Tests\Bridge\Local;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Bridge\Local\DistanceCalculator;
use Symfony\AI\Store\Bridge\Local\DistanceStrategy;
use Symfony\AI\Store\Bridge\Local\InMemoryStore;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\StoreInterface;
use Symfony\AI\Store\Test\StoreTestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(InMemoryStore::class)]
#[UsesClass(VectorDocument::class)]
#[UsesClass(Vector::class)]
final class InMemoryStoreTest extends StoreTestCase
{
    public static function createStore(array $options = []): StoreInterface
    {
        $distanceStrategy = $options['distance_strategy'] ?? DistanceStrategy::COSINE_DISTANCE;
        $distanceCalculator = new DistanceCalculator($distanceStrategy);

        return new InMemoryStore($distanceCalculator);
    }

    public static function documentsProvider(): iterable
    {
        yield 'empty documents' => [
            [],
        ];

        yield 'single document' => [
            [
                new VectorDocument(Uuid::v4(), new Vector([0.1, 0.2, 0.3])),
            ],
        ];

        yield 'multiple documents' => [
            [
                new VectorDocument(Uuid::v4(), new Vector([0.1, 0.1, 0.5])),
                new VectorDocument(Uuid::v4(), new Vector([0.7, -0.3, 0.0])),
                new VectorDocument(Uuid::v4(), new Vector([0.3, 0.7, 0.1])),
            ],
        ];

        yield 'documents with metadata' => [
            [
                new VectorDocument(Uuid::v4(), new Vector([0.1, 0.2, 0.3]), new Metadata(['title' => 'First'])),
                new VectorDocument(Uuid::v4(), new Vector([0.4, 0.5, 0.6]), new Metadata(['title' => 'Second'])),
            ],
        ];
    }

    public static function queryProvider(): iterable
    {
        $doc1 = new VectorDocument(Uuid::v4(), new Vector([0.1, 0.1, 0.5]));
        $doc2 = new VectorDocument(Uuid::v4(), new Vector([0.7, -0.3, 0.0]));
        $doc3 = new VectorDocument(Uuid::v4(), new Vector([0.3, 0.7, 0.1]));

        yield 'query with all results' => [
            [$doc1, $doc2, $doc3],
            new Vector([0.0, 0.1, 0.6]),
            [],
            3,
        ];

        yield 'query with maxItems' => [
            [$doc1, $doc2, $doc3],
            new Vector([0.0, 0.1, 0.6]),
            ['maxItems' => 1],
            1,
        ];

        yield 'query empty store' => [
            [],
            new Vector([0.1, 0.2, 0.3]),
            [],
            0,
        ];

        yield 'query with exact match' => [
            [
                new VectorDocument(Uuid::v4(), new Vector([0.0, 0.1, 0.6])),
                $doc1,
                $doc2,
            ],
            new Vector([0.0, 0.1, 0.6]),
            ['maxItems' => 1],
            1,
        ];
    }

    public function testStoreCannotSetup()
    {
        $store = new InMemoryStore();
        $store->setup();

        $result = $store->query(new Vector([0.0, 0.1, 0.6]));
        $this->assertCount(0, $result);
    }

    public function testStoreCanDrop()
    {
        $store = new InMemoryStore();
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([0.1, 0.1, 0.5])),
            new VectorDocument(Uuid::v4(), new Vector([0.7, -0.3, 0.0])),
            new VectorDocument(Uuid::v4(), new Vector([0.3, 0.7, 0.1])),
        );

        $result = $store->query(new Vector([0.0, 0.1, 0.6]));
        $this->assertCount(3, $result);

        $store->drop();

        $result = $store->query(new Vector([0.0, 0.1, 0.6]));
        $this->assertCount(0, $result);
    }

    public function testStoreCanSearchUsingCosineDistance()
    {
        $store = new InMemoryStore();
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([0.1, 0.1, 0.5])),
            new VectorDocument(Uuid::v4(), new Vector([0.7, -0.3, 0.0])),
            new VectorDocument(Uuid::v4(), new Vector([0.3, 0.7, 0.1])),
        );

        $result = $store->query(new Vector([0.0, 0.1, 0.6]));
        $this->assertCount(3, $result);
        $this->assertSame([0.1, 0.1, 0.5], $result[0]->vector->getData());

        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([0.1, 0.1, 0.5])),
            new VectorDocument(Uuid::v4(), new Vector([0.7, -0.3, 0.0])),
            new VectorDocument(Uuid::v4(), new Vector([0.3, 0.7, 0.1])),
        );

        $result = $store->query(new Vector([0.0, 0.1, 0.6]));
        $this->assertCount(6, $result);
        $this->assertSame([0.1, 0.1, 0.5], $result[0]->vector->getData());
    }

    public function testStoreCanSearchUsingCosineDistanceAndReturnCorrectOrder()
    {
        $store = new InMemoryStore();
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([0.1, 0.1, 0.5])),
            new VectorDocument(Uuid::v4(), new Vector([0.7, -0.3, 0.0])),
            new VectorDocument(Uuid::v4(), new Vector([0.3, 0.7, 0.1])),
            new VectorDocument(Uuid::v4(), new Vector([0.3, 0.1, 0.6])),
            new VectorDocument(Uuid::v4(), new Vector([0.0, 0.1, 0.6])),
        );

        $result = $store->query(new Vector([0.0, 0.1, 0.6]));
        $this->assertCount(5, $result);
        $this->assertSame([0.0, 0.1, 0.6], $result[0]->vector->getData());
        $this->assertSame([0.1, 0.1, 0.5], $result[1]->vector->getData());
        $this->assertSame([0.3, 0.1, 0.6], $result[2]->vector->getData());
        $this->assertSame([0.3, 0.7, 0.1], $result[3]->vector->getData());
        $this->assertSame([0.7, -0.3, 0.0], $result[4]->vector->getData());
    }

    public function testStoreCanSearchUsingCosineDistanceWithMaxItems()
    {
        $store = new InMemoryStore();
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([0.1, 0.1, 0.5])),
            new VectorDocument(Uuid::v4(), new Vector([0.7, -0.3, 0.0])),
            new VectorDocument(Uuid::v4(), new Vector([0.3, 0.7, 0.1])),
        );

        $this->assertCount(1, $store->query(new Vector([0.0, 0.1, 0.6]), [
            'maxItems' => 1,
        ]));
    }

    public function testStoreCanSearchUsingAngularDistance()
    {
        $store = static::createStore(['distance_strategy' => DistanceStrategy::ANGULAR_DISTANCE]);
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([1.0, 2.0, 3.0])),
            new VectorDocument(Uuid::v4(), new Vector([1.0, 5.0, 7.0])),
        );

        $result = $store->query(new Vector([1.2, 2.3, 3.4]));

        $this->assertCount(2, $result);
        $this->assertSame([1.0, 2.0, 3.0], $result[0]->vector->getData());
    }

    public function testStoreCanSearchUsingEuclideanDistance()
    {
        $store = static::createStore(['distance_strategy' => DistanceStrategy::EUCLIDEAN_DISTANCE]);
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([1.0, 5.0, 7.0])),
            new VectorDocument(Uuid::v4(), new Vector([1.0, 2.0, 3.0])),
        );

        $result = $store->query(new Vector([1.2, 2.3, 3.4]));

        $this->assertCount(2, $result);
        $this->assertSame([1.0, 2.0, 3.0], $result[0]->vector->getData());
    }

    public function testStoreCanSearchUsingManhattanDistance()
    {
        $store = static::createStore(['distance_strategy' => DistanceStrategy::MANHATTAN_DISTANCE]);
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([1.0, 2.0, 3.0])),
            new VectorDocument(Uuid::v4(), new Vector([1.0, 5.0, 7.0])),
        );

        $result = $store->query(new Vector([1.2, 2.3, 3.4]));

        $this->assertCount(2, $result);
        $this->assertSame([1.0, 2.0, 3.0], $result[0]->vector->getData());
    }

    public function testStoreCanSearchUsingChebyshevDistance()
    {
        $store = static::createStore(['distance_strategy' => DistanceStrategy::CHEBYSHEV_DISTANCE]);
        $store->add(
            new VectorDocument(Uuid::v4(), new Vector([1.0, 2.0, 3.0])),
            new VectorDocument(Uuid::v4(), new Vector([1.0, 5.0, 7.0])),
        );

        $result = $store->query(new Vector([1.2, 2.3, 3.4]));

        $this->assertCount(2, $result);
        $this->assertSame([1.0, 2.0, 3.0], $result[0]->vector->getData());
    }
}

