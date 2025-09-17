<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\AI\Store\Document\LoaderInterface;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\Document\TransformerInterface;
use Symfony\AI\Store\Document\VectorizerInterface;

/**
 * The Indexer class orchestrates the document processing pipeline for AI-powered document storage and retrieval.
 * 
 * ## Transformers
 * 
 * Transformers are components that modify or enhance documents during the indexing process. They implement
 * the TransformerInterface and are applied sequentially to all loaded documents before vectorization.
 * 
 * Common transformer use cases:
 * - Text preprocessing (normalization, cleaning, formatting)
 * - Content enrichment (adding metadata, extracting entities)
 * - Document splitting or chunking for optimal vector storage
 * - Content filtering based on quality or relevance criteria
 * 
 * Transformers receive an array of TextDocument objects and must return a modified array of TextDocument objects.
 * The transformation pipeline allows for complex document processing workflows by chaining multiple transformers.
 * 
 * ## Source
 * 
 * The source parameter defines what content should be indexed. It provides flexible input specification:
 * 
 * - **null**: Load all available content from the loader (no filtering)
 * - **string**: Load content from a specific source identifier (e.g., file path, URL, database table)
 * - **array**: Load content from multiple source identifiers for batch processing
 * 
 * The source is passed to the LoaderInterface, which handles the actual content retrieval logic.
 * This abstraction allows the same Indexer to work with different content sources (files, databases, APIs)
 * by simply changing the loader implementation.
 * 
 * The withSource() method enables creating new Indexer instances with different source configurations
 * while preserving other dependencies, supporting flexible indexing workflows.
 *
 * @author Christopher Hertel <mail@christopher-hertel.de>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
class Indexer implements IndexerInterface
{
    /**
     * @var array<string|null>
     */
    private array $sources = [];

    /**
     * @param string|array<string>|null $source
     * @param TransformerInterface[]    $transformers
     */
    public function __construct(
        private LoaderInterface $loader,
        private VectorizerInterface $vectorizer,
        private StoreInterface $store,
        string|array|null $source = null,
        private array $transformers = [],
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->sources = null === $source ? [] : (array) $source;
    }

    public function withSource(string|array $source): self
    {
        return new self($this->loader, $this->vectorizer, $this->store, $source, $this->transformers, $this->logger);
    }

    public function index(array $options = []): void
    {
        $this->logger->debug('Starting document processing', ['sources' => $this->sources, 'options' => $options]);

        $documents = [];
        if ([] === $this->sources) {
            // No specific source provided, load with null
            $documents = $this->loadSource(null);
        } else {
            foreach ($this->sources as $singleSource) {
                $documents = array_merge($documents, $this->loadSource($singleSource));
            }
        }

        if ([] === $documents) {
            $this->logger->debug('No documents to process', ['sources' => $this->sources]);

            return;
        }

        // Transform documents through all transformers
        foreach ($this->transformers as $transformer) {
            $documents = $transformer->transform($documents);
        }

        // Vectorize and store documents in chunks
        $chunkSize = $options['chunk_size'] ?? 50;
        $counter = 0;
        $chunk = [];
        foreach ($documents as $document) {
            $chunk[] = $document;
            ++$counter;

            if ($chunkSize === \count($chunk)) {
                $this->store->add(...$this->vectorizer->vectorizeTextDocuments($chunk));
                $chunk = [];
            }
        }

        // Handle remaining documents
        if ([] !== $chunk) {
            $this->store->add(...$this->vectorizer->vectorizeTextDocuments($chunk));
        }

        $this->logger->debug('Document processing completed', ['total_documents' => $counter]);
    }

    /**
     * @return TextDocument[]
     */
    private function loadSource(?string $source): array
    {
        $documents = [];
        foreach ($this->loader->load($source) as $document) {
            $documents[] = $document;
        }

        return $documents;
    }
}
