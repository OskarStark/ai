<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Tests\Bridge\MariaDb;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Bridge\MariaDb\Store;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\Exception\InvalidArgumentException;
use Symfony\AI\Store\Exception\RuntimeException;
use Symfony\Component\Uid\Uuid;

#[CoversClass(Store::class)]
#[UsesClass(VectorDocument::class)]
#[UsesClass(Vector::class)]
#[UsesClass(Metadata::class)]
final class StoreTest extends TestCase
{
    public function testQueryWithMaxScore()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);

        $store = new Store($pdo, 'embeddings_table', 'embedding_index', 'embedding');

        // Expected SQL query with max score
        $expectedQuery = <<<'SQL'
            SELECT id, VEC_ToText(embedding) embedding, metadata, VEC_DISTANCE_EUCLIDEAN(embedding, VEC_FromText(:embedding)) AS score
            FROM embeddings_table
            WHERE VEC_DISTANCE_EUCLIDEAN(embedding, VEC_FromText(:embedding)) <= :maxScore
            ORDER BY score ASC
            LIMIT 5
            SQL;

        $pdo->expects($this->once())
            ->method('prepare')
            ->with($expectedQuery)
            ->willReturn($statement);

        $uuid = Uuid::v4();
        $vectorData = [0.1, 0.2, 0.3];
        $maxScore = 0.8;

        $statement->expects($this->once())
            ->method('execute')
            ->with([
                'embedding' => json_encode($vectorData),
                'maxScore' => $maxScore,
            ]);

        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => $uuid->toBinary(),
                    'embedding' => json_encode($vectorData),
                    'metadata' => json_encode(['title' => 'Test Document']),
                    'score' => 0.85,
                ],
            ]);

        $results = $store->query(new Vector($vectorData), ['maxScore' => $maxScore]);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(VectorDocument::class, $results[0]);
        $this->assertSame(0.85, $results[0]->score);
        $this->assertSame(['title' => 'Test Document'], $results[0]->metadata->getArrayCopy());
    }

    public function testQueryWithoutMaxScore()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);

        $store = new Store($pdo, 'embeddings_table', 'embedding_index', 'embedding');

        // Expected SQL query without maxScore
        $expectedQuery = <<<'SQL'
            SELECT id, VEC_ToText(embedding) embedding, metadata, VEC_DISTANCE_EUCLIDEAN(embedding, VEC_FromText(:embedding)) AS score
            FROM embeddings_table

            ORDER BY score ASC
            LIMIT 5
            SQL;

        $pdo->expects($this->once())
            ->method('prepare')
            ->with($expectedQuery)
            ->willReturn($statement);

        $uuid = Uuid::v4();
        $vectorData = [0.1, 0.2, 0.3];

        $statement->expects($this->once())
            ->method('execute')
            ->with(['embedding' => json_encode($vectorData)]);

        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => $uuid->toBinary(),
                    'embedding' => json_encode($vectorData),
                    'metadata' => json_encode(['title' => 'Test Document']),
                    'score' => 0.95,
                ],
            ]);

        $results = $store->query(new Vector($vectorData));

        $this->assertCount(1, $results);
        $this->assertInstanceOf(VectorDocument::class, $results[0]);
        $this->assertSame(0.95, $results[0]->score);
    }

    public function testQueryWithCustomLimit()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);

        $store = new Store($pdo, 'embeddings_table', 'embedding_index', 'embedding');

        // Expected SQL query with custom limit
        $expectedQuery = <<<'SQL'
            SELECT id, VEC_ToText(embedding) embedding, metadata, VEC_DISTANCE_EUCLIDEAN(embedding, VEC_FromText(:embedding)) AS score
            FROM embeddings_table

            ORDER BY score ASC
            LIMIT 10
            SQL;

        $pdo->expects($this->once())
            ->method('prepare')
            ->with($expectedQuery)
            ->willReturn($statement);

        $vectorData = [0.1, 0.2, 0.3];

        $statement->expects($this->once())
            ->method('execute')
            ->with(['embedding' => json_encode($vectorData)]);

        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([]);

        $results = $store->query(new Vector($vectorData), ['limit' => 10]);

        $this->assertCount(0, $results);
    }

    public function testItCanDrop()
    {
        $pdo = $this->createMock(\PDO::class);

        $store = new Store($pdo, 'embeddings_table', 'embedding_index', 'embedding');

        $pdo->expects($this->once())
            ->method('exec')
            ->with('DROP TABLE IF EXISTS embeddings_table')
            ->willReturn(1);

        $store->drop();
    }

    public function testConstructorSuccessful()
    {
        $pdo = $this->createMock(\PDO::class);
        
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');
        
        $this->assertInstanceOf(Store::class, $store);
    }

    public function testSetupCreatesTableWithDefaultDimensions()
    {
        $pdo = $this->createMock(\PDO::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $pdo->expects($this->once())
            ->method('getAttribute')
            ->with(\PDO::ATTR_SERVER_VERSION)
            ->willReturn('11.8.0-MariaDB');

        $expectedSql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS test_table (
                id BINARY(16) NOT NULL PRIMARY KEY,
                metadata JSON,
                test_field VECTOR(1536) NOT NULL,
                VECTOR INDEX test_index (test_field)
            )
            SQL;

        $pdo->expects($this->once())
            ->method('exec')
            ->with($expectedSql);

        $store->setup();
    }

    public function testSetupCreatesTableWithCustomDimensions()
    {
        $pdo = $this->createMock(\PDO::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $pdo->expects($this->once())
            ->method('getAttribute')
            ->with(\PDO::ATTR_SERVER_VERSION)
            ->willReturn('11.8.0-MariaDB');

        $expectedSql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS test_table (
                id BINARY(16) NOT NULL PRIMARY KEY,
                metadata JSON,
                test_field VECTOR(768) NOT NULL,
                VECTOR INDEX test_index (test_field)
            )
            SQL;

        $pdo->expects($this->once())
            ->method('exec')
            ->with($expectedSql);

        $store->setup(['dimensions' => 768]);
    }

    public function testSetupThrowsExceptionForUnsupportedOptions()
    {
        $pdo = $this->createMock(\PDO::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The only supported option is "dimensions".');

        $store->setup(['unsupported' => 'option']);
    }

    public function testSetupThrowsExceptionForOldMariaDbVersion()
    {
        $pdo = $this->createMock(\PDO::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $pdo->expects($this->once())
            ->method('getAttribute')
            ->with(\PDO::ATTR_SERVER_VERSION)
            ->willReturn('11.6.0-MariaDB');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You need MariaDB >=11.7 to use this feature.');

        $store->setup();
    }

    public function testSetupThrowsExceptionForNonMariaDbDatabase()
    {
        $pdo = $this->createMock(\PDO::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $pdo->expects($this->once())
            ->method('getAttribute')
            ->with(\PDO::ATTR_SERVER_VERSION)
            ->willReturn('8.0.30');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You need MariaDB >=11.7 to use this feature.');

        $store->setup();
    }

    public function testAddSingleDocument()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $uuid = Uuid::v4();
        $document = new VectorDocument(
            $uuid,
            new Vector([0.1, 0.2, 0.3]),
            new Metadata(['title' => 'Test Document'])
        );

        $expectedSql = <<<'SQL'
            INSERT INTO test_table (id, metadata, test_field)
            VALUES (:id, :metadata, VEC_FromText(:vector))
            ON DUPLICATE KEY UPDATE metadata = :metadata, test_field = VEC_FromText(:vector)
            SQL;

        $pdo->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('execute')
            ->with([
                'id' => $uuid->toBinary(),
                'metadata' => json_encode(['title' => 'Test Document']),
                'vector' => json_encode([0.1, 0.2, 0.3]),
            ]);

        $store->add($document);
    }

    public function testAddMultipleDocuments()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $uuid1 = Uuid::v4();
        $uuid2 = Uuid::v4();
        $document1 = new VectorDocument(
            $uuid1,
            new Vector([0.1, 0.2, 0.3]),
            new Metadata(['title' => 'First Document'])
        );
        $document2 = new VectorDocument(
            $uuid2,
            new Vector([0.4, 0.5, 0.6]),
            new Metadata(['title' => 'Second Document'])
        );

        $expectedSql = <<<'SQL'
            INSERT INTO test_table (id, metadata, test_field)
            VALUES (:id, :metadata, VEC_FromText(:vector))
            ON DUPLICATE KEY UPDATE metadata = :metadata, test_field = VEC_FromText(:vector)
            SQL;

        $pdo->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->willReturn($statement);

        $executeCalls = [];
        $statement->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function ($params) use (&$executeCalls) {
                $executeCalls[] = $params;
                return true;
            });

        $store->add($document1, $document2);

        $this->assertCount(2, $executeCalls);
        $this->assertSame([
            'id' => $uuid1->toBinary(),
            'metadata' => json_encode(['title' => 'First Document']),
            'vector' => json_encode([0.1, 0.2, 0.3]),
        ], $executeCalls[0]);
        $this->assertSame([
            'id' => $uuid2->toBinary(),
            'metadata' => json_encode(['title' => 'Second Document']),
            'vector' => json_encode([0.4, 0.5, 0.6]),
        ], $executeCalls[1]);
    }

    public function testAddWithEmptyMetadata()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $uuid = Uuid::v4();
        $document = new VectorDocument($uuid, new Vector([0.1, 0.2, 0.3]));

        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('execute')
            ->with([
                'id' => $uuid->toBinary(),
                'metadata' => json_encode([]),
                'vector' => json_encode([0.1, 0.2, 0.3]),
            ]);

        $store->add($document);
    }

    public function testFromPdoCreatesStoreSuccessfully()
    {
        $pdo = $this->createMock(\PDO::class);
        
        $store = Store::fromPdo($pdo, 'test_table', 'test_index', 'test_field');
        
        $this->assertInstanceOf(Store::class, $store);
    }

    public function testFromPdoWithDefaultParameters()
    {
        $pdo = $this->createMock(\PDO::class);
        
        $store = Store::fromPdo($pdo, 'test_table');
        
        $this->assertInstanceOf(Store::class, $store);
    }

    public function testFromDbalWithValidPdoConnection()
    {
        $dbalConnection = $this->createMock(Connection::class);
        $pdo = $this->createMock(\PDO::class);
        
        $dbalConnection->expects($this->once())
            ->method('getNativeConnection')
            ->willReturn($pdo);
        
        $store = Store::fromDbal($dbalConnection, 'test_table', 'test_index', 'test_field');
        
        $this->assertInstanceOf(Store::class, $store);
    }

    public function testFromDbalThrowsExceptionForNonPdoDriver()
    {
        $dbalConnection = $this->createMock(Connection::class);
        $nonPdoConnection = new \stdClass();
        
        $dbalConnection->expects($this->once())
            ->method('getNativeConnection')
            ->willReturn($nonPdoConnection);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only DBAL connections using PDO driver are supported.');
        
        Store::fromDbal($dbalConnection, 'test_table');
    }

    public function testQueryWithEmptyResults()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('execute');

        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([]);

        $results = $store->query(new Vector([0.1, 0.2, 0.3]));

        $this->assertSame([], $results);
    }

    public function testQueryWithNullMetadata()
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);
        $store = new Store($pdo, 'test_table', 'test_index', 'test_field');

        $uuid = Uuid::v4();
        $vectorData = [0.1, 0.2, 0.3];

        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('execute');

        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => $uuid->toBinary(),
                    'embedding' => json_encode($vectorData),
                    'metadata' => null,
                    'score' => 0.95,
                ],
            ]);

        $results = $store->query(new Vector($vectorData));

        $this->assertCount(1, $results);
        $this->assertInstanceOf(VectorDocument::class, $results[0]);
        $this->assertSame([], $results[0]->metadata->getArrayCopy());
    }
}
