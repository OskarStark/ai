# DBAL Store

The DBAL Store provides a database-agnostic vector store implementation that automatically adapts to different database platforms using Doctrine DBAL.

## Features

- **Automatic Platform Detection**: Automatically detects and adapts to the underlying database platform
- **Lazy Connection**: Uses DBAL connection which supports lazy loading, solving issue #84
- **Platform Support**:
  - MariaDB >=11.7 (with native vector support)
  - PostgreSQL (with pgvector extension)
  - Easily extensible to support additional platforms

## Installation

```bash
composer require doctrine/dbal
```

## Usage

```php
use Doctrine\DBAL\DriverManager;
use Symfony\AI\Store\Bridge\Dbal\Store;

// Create DBAL connection
$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => 'mydb',
    'user' => 'user',
    'password' => 'pass',
    'serverVersion' => '11.7.0-MariaDB',
]);

// Create store - platform is automatically detected
$store = new Store($connection, 'embeddings_table');

// Initialize the store (creates tables and indexes)
$store->initialize(['dimensions' => 1536]);

// Use the store...
```

## Platform-Specific Behavior

### MariaDB
- Uses native `VECTOR` type and `VEC_FromText`/`VEC_ToText` functions
- Creates `VECTOR INDEX` for efficient similarity search
- Uses `VEC_DISTANCE_EUCLIDEAN` for distance calculations

### PostgreSQL
- Requires pgvector extension
- Uses `vector` type with array syntax
- Creates ivfflat index with cosine similarity
- Uses `<->` operator for distance calculations

## Benefits Over Platform-Specific Stores

1. **Single Implementation**: One store implementation handles multiple database platforms
2. **Lazy Connection**: DBAL connections are lazy by default, solving the "heavy constructor" issue
3. **Easy Migration**: Switch between supported databases without code changes
4. **Future-Proof**: Easy to add support for new platforms as they add vector capabilities