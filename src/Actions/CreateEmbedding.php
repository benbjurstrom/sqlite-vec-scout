<?php

namespace BenBjurstrom\SqliteVecScout\Actions;

use BenBjurstrom\SqliteVecScout\IndexConfig;
use BenBjurstrom\SqliteVecScout\Models\Embedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use BenBjurstrom\SqliteVecScout\Vector;

class CreateEmbedding
{
    /**
     * Create or update an embedding for a given model
     */
    public static function handle(
        Model $model,
        IndexConfig $config
    ): ?Embedding {

        // validate the model is searchable
        if (! method_exists($model, 'toSearchableArray') || ! method_exists($model, 'scoutMetadata')) {
            throw new \RuntimeException('Model '.get_class($model).' does not implement the Searchable trait.');
        }

        // Get the searchable data
        $searchableData = $model->toSearchableArray();
        if (empty($searchableData)) {
            return null;
        }

        // Merge with scout metadata
        $data = array_merge(
            $searchableData,
            $model->scoutMetadata(),
            ['__soft_deleted' => method_exists($model, 'trashed') ? $model->trashed() : false]
        );

        $content = static::arrayToLabeledText($data);

        // Check if we already have a vector for this model with the same hash
        $contentHash = HashContent::handle($content);

        if ($embedding = static::existingEmbedding($model, $contentHash, $config)) {
            return $embedding;
        }

        // If not fetch an embedding for the content and save it
        $vector = $config->handler::handle($content, $config);

        return static::updateOrCreateEmbedding($model, $contentHash, $vector, $config);
    }

    /**
     * Convert array data to labeled text format
     *
     * @param  array<int|string, string|array<string, string>>  $data
     */
    protected static function arrayToLabeledText(array $data): string
    {
        if (array_is_list($data) && is_string($data[0])) {
            return $data[0];
        }

        return collect($data)
            ->map(function ($value, $key) {
                // Use Laravel's data_get() for nested arrays
                if (is_array($value)) {
                    $value = data_get($value, '*');
                }

                // Use Laravel's Str::of() for string manipulation
                return Str::of((string) $key)
                    ->append(': ')
                    ->append(match (true) {
                        is_array($value) => json_encode($value),
                        is_bool($value) => $value ? 'true' : 'false',
                        is_null($value) => 'null',
                        default => $value
                    });
            })
            ->join(PHP_EOL);
    }

    /**
     * Find existing embedding with matching hash
     */
    protected static function existingEmbedding(
        Model $model,
        string $contentHash,
        IndexConfig $config
    ): ?Embedding {
        return (new Embedding)
            ->forModel($model)
            ->where('embeddable_type', get_class($model))
            ->where('embeddable_id', $model->getKey())
            ->where('content_hash', $contentHash)
            ->where('embedding_model', $config->model)
            ->first();
    }

    /**
     * Create or update embedding record
     */
    protected static function updateOrCreateEmbedding(
        Model $model,
        string $contentHash,
        Vector $vector,
        IndexConfig $config
    ): Embedding {
        Log::info('Updating embedding', [
            'id' => $model->getKey(),
            'model' => get_class($model),
            'embedding_model' => $config->model,
        ]);

        $attributes = [
            'embedding_model' => $config->model,
            'content_hash' => $contentHash,
            'vector' => $vector,
        ];

        // Add __soft_deleted if Scout's soft delete is enabled
        if (config('scout.soft_delete', false) && method_exists($model, 'trashed')) {
            $attributes['__soft_deleted'] = $model->trashed();
        }

        return (new Embedding)
            ->forModel($model)
            ->updateOrCreate(
                [
                    'embeddable_type' => get_class($model),
                    'embeddable_id' => $model->getKey(),
                ],
                $attributes
            );
    }
}
