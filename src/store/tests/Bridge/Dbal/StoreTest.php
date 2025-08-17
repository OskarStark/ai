<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Tests\Bridge\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Bridge\Dbal\Store;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\Exception\RuntimeException;
use Symfony\Component\Uid\Uuid;

#[CoversClass(Store::class)]
final class StoreTest extends TestCase
{
    public function testConstructorThrowsWithoutDoctrineDbal(): void
    {
        if (class_exists(Connection::class)) {
            $this->markTestSkipped('This test requires doctrine/dbal to not be installed');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('For using DbalStore as retrieval vector store, the doctrine/dbal package needs to be installed.');

        $connection = $this->createMock(Connection::class);
        new Store($connection, 'embeddings');
    }

    public function testAddWithUnsupportedPlatform(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(SQLitePlatform::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $store = new Store($connection, 'embeddings');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported database platform: '.SQLitePlatform::class);

        $store->add(new VectorDocument(
            id: Uuid::v4(),
            vector: new Vector([0.1, 0.2, 0.3]),
            metadata: new Metadata(['title' => 'Test']),
        ));
    }

    public function testAddForMariaDB(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(MariaDBPlatform::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $uuid = Uuid::v4();
        $vectorData = [0.1, 0.2, 0.3];
        $metadata = ['title' => 'Test Document'];

        $expectedSql = <<<'SQL'
            INSERT INTO embeddings (id, metadata, embedding)
            VALUES (:id, :metadata, VEC_FromText(:vector))
            ON DUPLICATE KEY UPDATE metadata = :metadata, embedding = VEC_FromText(:vector)
            SQL;

        $connection->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, [
                'id' => $uuid->toBinary(),
                'metadata' => json_encode($metadata),
                'vector' => json_encode($vectorData),
            ]);

        $store = new Store($connection, 'embeddings');
        $store->add(new VectorDocument(
            id: $uuid,
            vector: new Vector($vectorData),
            metadata: new Metadata($metadata),
        ));
    }

    public function testAddForPostgreSQL(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(PostgreSQLPlatform::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $uuid = Uuid::v4();
        $vectorData = [0.1, 0.2, 0.3];
        $metadata = ['title' => 'Test Document'];

        $expectedSql = 'INSERT INTO embeddings (id, metadata, embedding)
            VALUES (:id, :metadata, :vector)
            ON CONFLICT (id) DO UPDATE SET metadata = EXCLUDED.metadata, embedding = EXCLUDED.embedding';

        $connection->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, [
                'id' => $uuid->toRfc4122(),
                'metadata' => json_encode($metadata),
                'vector' => '[0.1,0.2,0.3]',
            ]);

        $store = new Store($connection, 'embeddings');
        $store->add(new VectorDocument(
            id: $uuid,
            vector: new Vector($vectorData),
            metadata: new Metadata($metadata),
        ));
    }

    public function testQueryWithMariaDB(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(MariaDBPlatform::class);
        $result = $this->createMock(Result::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $uuid = Uuid::v4();
        $vectorData = [0.1, 0.2, 0.3];
        $minScore = 0.8;

        $expectedSql = <<<'SQL'
            SELECT id, VEC_ToText(embedding) embedding, metadata, VEC_DISTANCE_EUCLIDEAN(embedding, VEC_FromText(:embedding)) AS score
            FROM embeddings
            WHERE VEC_DISTANCE_EUCLIDEAN(embedding, VEC_FromText(:embedding)) >= :minScore
            ORDER BY score ASC
            LIMIT 10
            SQL;

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, [
                'embedding' => json_encode($vectorData),
                'minScore' => $minScore,
            ])
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => $uuid->toBinary(),
                    'embedding' => json_encode($vectorData),
                    'metadata' => json_encode(['title' => 'Test Document']),
                    'score' => 0.85,
                ],
            ]);

        $store = new Store($connection, 'embeddings');
        $results = $store->query(new Vector($vectorData), ['limit' => 10], $minScore);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(VectorDocument::class, $results[0]);
        $this->assertSame(0.85, $results[0]->score);
        $this->assertSame(['title' => 'Test Document'], $results[0]->metadata->getArrayCopy());
    }

    public function testQueryWithPostgreSQL(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(PostgreSQLPlatform::class);
        $result = $this->createMock(Result::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $uuid = Uuid::v4();
        $vectorData = [0.1, 0.2, 0.3];

        $expectedSql = 'SELECT id, embedding AS embedding, metadata, (embedding <-> :embedding) AS score
             FROM embeddings
             
             ORDER BY score ASC
             LIMIT 5';

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, [
                'embedding' => '[0.1,0.2,0.3]',
            ])
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => $uuid->toRfc4122(),
                    'embedding' => '[0.1,0.2,0.3]',
                    'metadata' => json_encode(['title' => 'Test Document']),
                    'score' => 0.95,
                ],
            ]);

        $store = new Store($connection, 'embeddings');
        $results = $store->query(new Vector($vectorData));

        $this->assertCount(1, $results);
        $this->assertInstanceOf(VectorDocument::class, $results[0]);
        $this->assertSame(0.95, $results[0]->score);
    }

    public function testInitializeForMariaDB(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(MariaDBPlatform::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->expects($this->once())
            ->method('getServerVersion')
            ->willReturn('11.7.0-MariaDB');

        $expectedSql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS embeddings (
                id BINARY(16) NOT NULL PRIMARY KEY,
                metadata JSON,
                embedding VECTOR(768) NOT NULL,
                VECTOR INDEX embedding_idx (embedding)
            )
            SQL;

        $connection->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql);

        $store = new Store($connection, 'embeddings', 'embedding_idx');
        $store->initialize(['dimensions' => 768]);
    }

    public function testInitializeForPostgreSQL(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(PostgreSQLPlatform::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->expects($this->exactly(3))
            ->method('executeStatement')
            ->willReturnCallback(function ($sql) {
                static $callCount = 0;
                $callCount++;

                $expectedSqls = [
                    'CREATE EXTENSION IF NOT EXISTS vector',
                    'CREATE TABLE IF NOT EXISTS embeddings (
                    id UUID PRIMARY KEY,
                    metadata JSONB,
                    embedding vector(768) NOT NULL
                )',
                    'CREATE INDEX IF NOT EXISTS embeddings_embedding_idx ON embeddings USING ivfflat (embedding vector_cosine_ops)',
                ];

                $this->assertSame($expectedSqls[$callCount - 1], $sql);

                return 0;
            });

        $store = new Store($connection, 'embeddings');
        $store->initialize(['vector_size' => 768]);
    }

    public function testInitializeWithUnsupportedPlatform(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(SQLitePlatform::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $store = new Store($connection, 'embeddings');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported database platform: '.SQLitePlatform::class);

        $store->initialize();
    }
}