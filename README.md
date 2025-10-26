<p align="center"><img src="https://github.com/user-attachments/assets/9c55eb67-f44f-442d-86b9-e0969213862c" width="600" alt="Logo"></a></p>

<p align="center">
<a href="https://packagist.org/packages/benbjurstrom/sqlite-vec-scout"><img src="https://img.shields.io/packagist/v/benbjurstrom/sqlite-vec-scout.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://github.com/benbjurstrom/sqlite-vec-scout/actions?query=workflow%3Arun-tests+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/benbjurstrom/sqlite-vec-scout/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Tests Action Status"></a>
<a href="https://github.com/benbjurstrom/sqlite-vec-scout/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/benbjurstrom/sqlite-vec-scout/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square" alt="GitHub Code Style Action Status"></a>
</p>

# sqlite-vec driver for Laravel Scout

Use the sqlite-vec extension with Laravel Scout for vector similarity search.

To see a full example showing how to use this package check out [benbjurstrom/sqlite-vec-scout-demo](https://github.com/benbjurstrom/sqlite-vec-scout-demo).

## 🚀 Quick Start

#### 1. Install the package using composer:
```bash
composer require benbjurstrom/sqlite-vec-scout
```

#### 2. Publish the scout config and the package config:
```bash
php artisan vendor:publish --tag="scout-config"
php artisan vendor:publish --tag="sqlite-vec-scout-config"
```

This is the contents of the published `sqlite-vec-scout.php` config file. By default it contains 3 different indexes, one for OpenAI, one for Google Gemini, and one for testing. The rest of this guide will use the OpenAI index as an example.

```php
return [
    /*
    |--------------------------------------------------------------------------
    | sqlite-vec Extension Path
    |--------------------------------------------------------------------------
    |
    | Point this to your compiled vec0 module when PDO::loadExtension is
    | available. Leave it null when sqlite-vec is baked into your PHP binary.
    |
    */
    'extension_path' => env('SQLITE_VEC_EXTENSION_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Index Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can define the configuration for different embedding indexes.
    | Each index can have its own specific configuration options.
    |
    */
    'indexes' => [
        'openai' => [
            'handler' => Handlers\OpenAiHandler::class,
            'model' => 'text-embedding-3-small',
            'dimensions' => 256, // See Reducing embedding dimensions https://platform.openai.com/docs/guides/embeddings#use-cases
            'url' => 'https://api.openai.com/v1',
            'api_key' => env('OPENAI_API_KEY'),
            'table' => 'openai_embeddings',
        ],
        'gemini' => [
            'handler' => Handlers\GeminiHandler::class,
            'model' => 'text-embedding-004',
            'dimensions' => 256,
            'url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => env('GEMINI_API_KEY'),
            'table' => 'gemini_embeddings',
            'task' => 'SEMANTIC_SIMILARITY', // https://ai.google.dev/api/embeddings#tasktype
        ],
        'ollama' => [
            'handler' => Handlers\OllamaHandler::class,
            'model' => 'nomic-embed-text',
            'dimensions' => 768,
            'url' => 'http://localhost:11434/api/embeddings',
            'api_key' => 'none',
            'table' => 'ollama_embeddings',
        ],
        'fake' => [ // Used for testing
            'handler' => Handlers\FakeHandler::class,
            'model' => 'fake',
            'dimensions' => 3,
            'url' => 'https://example.com',
            'api_key' => '123',
            'table' => 'fake_embeddings',
        ],
    ],
];
```

#### 3. Set the scout driver to sqlite-vec in your .env file and add your OpenAI API key:
```env
SCOUT_DRIVER=sqlite-vec
OPENAI_API_KEY=your-api-key
```

#### 4. Run the scout index command to create a migration file for your embeddings:
```bash
php artisan scout:index openai
php artisan migrate
```

#### 5. Update the model you wish to make searchable:
Add the `HasEmbeddings` and `Searchable` traits to your model. Additionally add a `searchableAs()` method that returns the name of your index. Finally implement `toSearchableArray()` with the content from the model you want converted into an embedding.

```php
use BenBjurstrom\SqliteVecScout\Models\Concerns\HasEmbeddings;
use Laravel\Scout\Searchable;

class YourModel extends Model
{
    use HasEmbeddings, Searchable;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'openai';
    }

    /**
     * Get the indexable content for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
```

## 🔍 Usage

### Create embeddings for your models:
Laravel Scout uses eloquent model observers to automatically keep your search index in sync anytime your Searchable models change. 

This package uses this functionality automatically generate embeddings for your models when they are saved or updated; or remove them when your models are deleted.

If you want to manually generate embeddings for existing models you can use the artisan command below. See the [Scout documentation](https://laravel.com/docs/8.x/scout) for more information.

```bash
artisan scout:import "App\Models\YourModel"
```

### Search using vector similarity:
You can use the typical Scout syntax to search your models. For example:

```php
$results = YourModel::search('your search query')->get();
```

Note that the text of your query will be converted into a vector embedding using the model index's configured handler. It's important that the same model is used for both indexing and searching.

### Search using existing vectors:
You can also pass an existing embedding vector as a search parameter. This can be useful to find related models. For example:
```php
$vector = $someModel->embedding->vector;
$results = YourModel::search($vector)->get();
```

### Evaluate search results:
All search queries will be ordered by similarity to the given input and include the embedding relationship. The value of the nearest neighbor search can be accessed as follows:
```php
$results = YourModel::search('your search query')->get();
$results->first()->embedding->neighbor_distance; // 0.26834 (example value)
```

The larger the distance the less similar the result is to the input.

## 🛠Using custom handlers
By default this package uses OpenAI to generate embeddings. To do this it uses the [OpenAiHandler](https://github.com/benbjurstrom/sqlite-vec-scout/blob/main/src/Handlers/OpenAiHandler.php) class paired with the openai index found in the packages [config file](https://github.com/benbjurstrom/sqlite-vec-scout/blob/main/config/sqlite-vec-scout.php).

You can generate embeddings from other providers by adding a custom Handler. A handler is a simple class defined in the [HandlerContract](https://github.com/benbjurstrom/sqlite-vec-scout/blob/main/src/HandlerContract.php) that takes a string, a config object, and returns a `BenBjurstrom\\SqliteVecScout\\Vector` instance.

Whatever api calls or logic is needed to turn a string into a vector should be defined in the `handle` method of your custom handler.

If you need to pass api keys, embedding dimensions, or any other configuration to your handler you can define them in the `config/sqlite-vec-scout.php` file.

## Installing sqlite-vec locally
`sqlite-vec-scout` no longer provides a PHP fallback for vector math – the native `sqlite-vec` extension **must** be loaded on the same SQLite connection that Laravel uses. The service provider validates this at boot time and will throw a runtime exception if `vec0` is unavailable.

`sqlite-vec` ships as a small SQLite extension that can be compiled from source or installed via language-specific packages. The official project maintains [comprehensive installation docs](./sqlite-vec%20docs/README.md) that cover macOS, Linux, Windows, and WebAssembly builds. For PHP-specific guidance on compiling binaries with extension loading enabled — including a full static-php-cli build script — read Ben Bjurstrom's guide: <https://benbjurstrom.com/sqlite-vec-php>.

To see if your current PHP binary can load extensions dynamically run:

```bash
php -d detect_unicode=0 -r 'var_dump(method_exists(new PDO("sqlite::memory:"), "loadExtension"));'
```

When the command prints `bool(true)`, download a `vec0` build, expose its full path via `SQLITE_VEC_EXTENSION_PATH` (or the `sqlite-vec-scout.extension_path` config key), and the service provider will call `PDO::loadExtension()` before validating the module. If the command returns `bool(false)` you must compile sqlite-vec directly into PHP — the blog post above demonstrates how to do this with static-php-cli and ensures the `SQLITE_ENABLE_LOAD_EXTENSION` flag is set for both `sqlite3` and `pdo_sqlite`.

## 👏 Credits

- [Ben Bjurstrom](https://github.com/benbjurstrom)
- [All Contributors](../../contributors)

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
