# How the Indexer Works in Symfony AI

The Symfony AI Store component includes a powerful `Indexer` class that serves as the orchestrator for processing documents into vector embeddings for storage and retrieval. It is part of the symfony/ai-store component.

```bash
composer require symfony/ai-store
```

Understanding how it works is key to building effective RAG (Retrieval-Augmented Generation) applications.

## The Indexing Pipeline

The `Indexer` follows a well-defined pipeline that transforms raw documents into searchable vector representations:

```
Load → Filter → Transform → Vectorize → Store
```

### 1. Document Loading

The indexer starts by loading documents from various sources using a `LoaderInterface` implementation. Sources can be:
- File paths
- URLs  
- Database queries
- Or any custom data source

```php
use Symfony\AI\Store\Indexer;

$indexer = new Indexer($loader, $vectorizer, $store, $source);
$indexer->index();
```

### 2. Filtering Documents

After loading, documents pass through optional filters that remove unwanted content. Filters implement `FilterInterface` and work as streaming processors:

```php
public function filter(iterable $documents, array $options = []): iterable;
```

For example, you might filter out documents containing spam or specific content patterns:

```php
use Symfony\AI\Store\Document\Filter\TextContainsFilter;
use Symfony\AI\Store\Indexer;

$filter = new TextContainsFilter('Spam');
$indexer = new Indexer($loader, $vectorizer, $store, null, [$filter]);
```

### 3. Document Transformation

Transformers modify documents after filtering - perfect for chunking large documents, cleaning text, or enriching metadata:

```php
use Symfony\AI\Store\Document\Transformer\TextReplaceTransformer;

$transformer = new TextReplaceTransformer('old text', 'new text');
```

### 4. Vectorization and Storage

Finally, documents are vectorized using AI embeddings and stored in configurable chunks:

```php
/** @var VectorDocument[] */
$vectorDocuments = $this->vectorizer->vectorizeTextDocuments($chunk);
$this->store->add(...$vectorDocuments);
```

## Key Features

**Immutable Design**: The `withSource()` method creates new indexer instances, allowing you to reuse configured pipelines with different data sources.

**Flexible Pipeline**: Mix and match loaders, filters, transformers, vectorizers, and stores to build custom indexing workflows.

**Multiple Sources**: Handle single sources or arrays of sources in a single indexing operation.

## Standalone Example

```php
use Symfony\AI\Platform\Bridge\OpenAi\Embeddings;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\AI\Store\Bridge\Local\InMemoryStore;
use Symfony\AI\Store\Document\Loader\RssFeedLoader;
use Symfony\AI\Store\Document\Transformer\TextSplitTransformer;
use Symfony\AI\Store\Document\Vectorizer;
use Symfony\AI\Store\Indexer;
use Symfony\Component\HttpClient\HttpClient;

$platform = PlatformFactory::create($apiKey, HttpClient::create());
$store = new InMemoryStore();
$vectorizer = new Vectorizer($platform, new Embeddings('text-embedding-3-small'));
$indexer = new Indexer(
    loader: new RssFeedLoader(HttpClient::create()),
    vectorizer: $vectorizer,
    store: $store,
    source: 'https://feeds.feedburner.com/symfony/blog',
    transformers: [
        new TextSplitTransformer(chunkSize: 500, overlap: 100),
    ],
);

$indexer->index();

$vector = $vectorizer->vectorize('Week of Symfony');
$results = $store->query($vector);
foreach ($results as $i => $document) {
    echo sprintf("%d. %s\n", $i + 1, substr($document->id, 0, 40).'...');
}
```

## Symfony AI Bundle Configuration

When using the Symfony AI Bundle, you can configure indexers declaratively through YAML:

```bash
composer require symfony/ai-bundle
```

```yaml
ai:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
    store:
        chroma_db:
            symfony_blog:
                collection: 'blog_posts'
    vectorizer:
        openai:
            model:
                class: 'Symfony\AI\Platform\Bridge\OpenAi\Embeddings'
                name: 'text-embedding-ada-002'
    indexer:
        blog:
            loader: 'Symfony\AI\Store\Document\Loader\RssFeedLoader'
            source: 'https://feeds.feedburner.com/symfony/blog'
            filters:
                - 'app.filter.spam_remover'
            transformers:
                - 'Symfony\AI\Store\Document\Transformer\TextTrimTransformer'
            vectorizer: 'ai.vectorizer.openai'
            store: 'ai.store.chroma_db.symfony_blog'

services:
    app.filter.spam_remover:
        class: 'Symfony\AI\Store\Document\Filter\TextContainsFilter'
        arguments:
            $needle: 'Spam'
            $caseSensitive: false
```

Once configured, you can run the indexer using the console command:

```bash
bin/console ai:store:index blog
```

The indexer's modular design makes it easy to build sophisticated document processing pipelines while maintaining clean, testable code. Whether you're indexing documentation, processing user content, or building knowledge bases, the Symfony AI Indexer provides the foundation for robust vector search applications.