<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Bridge\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Platform\Vector\VectorInterface;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\Exception\InvalidArgumentException;
use Symfony\AI\Store\Exception\RuntimeException;
use Symfony\AI\Store\InitializableStoreInterface;
use Symfony\AI\Store\VectorStoreInterface;
use Symfony\Component\Uid\Uuid;

/**
 * A DBAL-based vector store that automatically handles platform-specific
 * storage strategies for different database systems.
 *
 * Supported platforms:
 * - MariaDB >=11.7 (with native vector support)
 * - PostgreSQL (with pgvector extension)
 *
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final readonly class Store implements VectorStoreInterface, InitializableStoreInterface
{
    /**
     * @param string $tableName       The name of the table
     * @param string $indexName       The name of the vector search index
     * @param string $vectorFieldName The name of the field in the index that contains the vector
     */
    public function __construct(
        private Connection $connection,
        private string $tableName,
        private string $indexName = 'embedding',
        private string $vectorFieldName = 'embedding',
    ) {
        if (!class_exists(Connection::class)) {
            throw new RuntimeException('For using DbalStore as retrieval vector store, the doctrine/dbal package needs to be installed.');
        }
    }

    public function add(VectorDocument ...$documents): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MariaDBPlatform) {
            $this->addForMariaDB($documents);
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addForPostgreSQL($documents);
        } else {
            throw new RuntimeException(\sprintf('Unsupported database platform: %s', $platform::class));
        }
    }

    /**
     * @param array{
     *     limit?: positive-int,
     * } $options
     */
    public function query(Vector $vector, array $options = [], ?float $minScore = null): array
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MariaDBPlatform) {
            return $this->queryForMariaDB($vector, $options, $minScore);
        } elseif ($platform instanceof PostgreSQLPlatform) {
            return $this->queryForPostgreSQL($vector, $options, $minScore);
        } else {
            throw new RuntimeException(\sprintf('Unsupported database platform: %s', $platform::class));
        }
    }

    /**
     * @param array{
     *     dimensions?: positive-int,
     *     vector_size?: positive-int,
     * } $options
     */
    public function initialize(array $options = []): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MariaDBPlatform) {
            $this->initializeForMariaDB($options);
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->initializeForPostgreSQL($options);
        } else {
            throw new RuntimeException(\sprintf('Unsupported database platform: %s', $platform::class));
        }
    }

    /**
     * @param VectorDocument[] $documents
     *
     * @throws DBALException
     */
    private function addForMariaDB(array $documents): void
    {
        $sql = \sprintf(
            <<<'SQL'
                INSERT INTO %1$s (id, metadata, %2$s)
                VALUES (:id, :metadata, VEC_FromText(:vector))
                ON DUPLICATE KEY UPDATE metadata = :metadata, %2$s = VEC_FromText(:vector)
                SQL,
            $this->tableName,
            $this->vectorFieldName,
        );

        foreach ($documents as $document) {
            $this->connection->executeStatement($sql, [
                'id' => $document->id->toBinary(),
                'metadata' => json_encode($document->metadata->getArrayCopy()),
                'vector' => json_encode($document->vector->getData()),
            ]);
        }
    }

    /**
     * @param VectorDocument[] $documents
     *
     * @throws DBALException
     */
    private function addForPostgreSQL(array $documents): void
    {
        $sql = \sprintf(
            'INSERT INTO %1$s (id, metadata, %2$s)
            VALUES (:id, :metadata, :vector)
            ON CONFLICT (id) DO UPDATE SET metadata = EXCLUDED.metadata, %2$s = EXCLUDED.%2$s',
            $this->tableName,
            $this->vectorFieldName,
        );

        foreach ($documents as $document) {
            $this->connection->executeStatement($sql, [
                'id' => $document->id->toRfc4122(),
                'metadata' => json_encode($document->metadata->getArrayCopy(), \JSON_THROW_ON_ERROR),
                'vector' => $this->toPgvector($document->vector),
            ]);
        }
    }

    /**
     * @param array{limit?: positive-int} $options
     *
     * @return VectorDocument[]
     *
     * @throws DBALException
     */
    private function queryForMariaDB(Vector $vector, array $options, ?float $minScore): array
    {
        $sql = \sprintf(
            <<<'SQL'
                SELECT id, VEC_ToText(%1$s) embedding, metadata, VEC_DISTANCE_EUCLIDEAN(%1$s, VEC_FromText(:embedding)) AS score
                FROM %2$s
                %3$s
                ORDER BY score ASC
                LIMIT %4$d
                SQL,
            $this->vectorFieldName,
            $this->tableName,
            null !== $minScore ? \sprintf('WHERE VEC_DISTANCE_EUCLIDEAN(%1$s, VEC_FromText(:embedding)) >= :minScore', $this->vectorFieldName) : '',
            $options['limit'] ?? 5,
        );

        $params = ['embedding' => json_encode($vector->getData())];

        if (null !== $minScore) {
            $params['minScore'] = $minScore;
        }

        $documents = [];
        $result = $this->connection->executeQuery($sql, $params);

        foreach ($result->fetchAllAssociative() as $row) {
            $documents[] = new VectorDocument(
                id: Uuid::fromBinary($row['id']),
                vector: new Vector(json_decode((string) $row['embedding'], true)),
                metadata: new Metadata(json_decode($row['metadata'] ?? '{}', true)),
                score: $row['score'],
            );
        }

        return $documents;
    }

    /**
     * @param array{limit?: positive-int} $options
     *
     * @return VectorDocument[]
     *
     * @throws DBALException
     */
    private function queryForPostgreSQL(Vector $vector, array $options, ?float $minScore): array
    {
        $sql = \sprintf(
            'SELECT id, %s AS embedding, metadata, (%s <-> :embedding) AS score
             FROM %s
             %s
             ORDER BY score ASC
             LIMIT %d',
            $this->vectorFieldName,
            $this->vectorFieldName,
            $this->tableName,
            null !== $minScore ? "WHERE ({$this->vectorFieldName} <-> :embedding) >= :minScore" : '',
            $options['limit'] ?? 5,
        );

        $params = [
            'embedding' => $this->toPgvector($vector),
        ];
        if (null !== $minScore) {
            $params['minScore'] = $minScore;
        }

        $documents = [];
        $result = $this->connection->executeQuery($sql, $params);

        foreach ($result->fetchAllAssociative() as $row) {
            $documents[] = new VectorDocument(
                id: Uuid::fromString($row['id']),
                vector: new Vector($this->fromPgvector($row['embedding'])),
                metadata: new Metadata(json_decode($row['metadata'] ?? '{}', true, 512, \JSON_THROW_ON_ERROR)),
                score: $row['score'],
            );
        }

        return $documents;
    }

    /**
     * @param array{dimensions?: positive-int} $options
     *
     * @throws DBALException
     */
    private function initializeForMariaDB(array $options): void
    {
        if ([] !== $options && !\array_key_exists('dimensions', $options)) {
            throw new InvalidArgumentException('The only supported option is "dimensions"');
        }

        $serverVersion = $this->connection->getServerVersion();

        if (!str_contains((string) $serverVersion, 'MariaDB') || version_compare($serverVersion, '11.7.0') < 0) {
            throw new InvalidArgumentException('You need MariaDB >=11.7 to use this feature');
        }

        $this->connection->executeStatement(
            \sprintf(
                <<<'SQL'
                    CREATE TABLE IF NOT EXISTS %1$s (
                        id BINARY(16) NOT NULL PRIMARY KEY,
                        metadata JSON,
                        %2$s VECTOR(%4$d) NOT NULL,
                        VECTOR INDEX %3$s (%2$s)
                    )
                    SQL,
                $this->tableName,
                $this->vectorFieldName,
                $this->indexName,
                $options['dimensions'] ?? 1536,
            ),
        );
    }

    /**
     * @param array{vector_size?: positive-int} $options
     *
     * @throws DBALException
     */
    private function initializeForPostgreSQL(array $options): void
    {
        $this->connection->executeStatement('CREATE EXTENSION IF NOT EXISTS vector');

        $this->connection->executeStatement(
            \sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id UUID PRIMARY KEY,
                    metadata JSONB,
                    %s vector(%d) NOT NULL
                )',
                $this->tableName,
                $this->vectorFieldName,
                $options['vector_size'] ?? 1536,
            ),
        );
        $this->connection->executeStatement(
            \sprintf(
                'CREATE INDEX IF NOT EXISTS %s_%s_idx ON %s USING ivfflat (%s vector_cosine_ops)',
                $this->tableName,
                $this->vectorFieldName,
                $this->tableName,
                $this->vectorFieldName,
            ),
        );
    }

    private function toPgvector(VectorInterface $vector): string
    {
        return '['.implode(',', $vector->getData()).']';
    }

    /**
     * @return float[]
     */
    private function fromPgvector(string $vector): array
    {
        return json_decode($vector, true, 512, \JSON_THROW_ON_ERROR);
    }
}