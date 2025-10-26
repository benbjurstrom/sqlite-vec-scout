<?php

namespace BenBjurstrom\SqliteVecScout\Handlers;

use BenBjurstrom\SqliteVecScout\HandlerContract;
use BenBjurstrom\SqliteVecScout\IndexConfig;
use BenBjurstrom\SqliteVecScout\Vector;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiHandler implements HandlerContract
{
    public static function handle(string $input, IndexConfig $config): Vector
    {
        $cacheKey = $config->name.':'.$config->model.':'.sha1($input). 4;

        $embedding = Cache::rememberForever($cacheKey, function () use ($input, $config) {
            $url = $config->url.'/models/'.$config->model.':embedContent?key='.$config->apiKey;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => 'models/'.$config->model,
                'output_dimensionality' => $config->dimensions,
                'taskType' => $config->task,
                'content' => [
                    'parts' => [
                        ['text' => $input],
                    ],
                ],
            ]);

            static::validateResponse($response);

            return static::extractEmbedding($response);
        });

        return new Vector($embedding);
    }

    protected static function validateResponse(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                'Gemini API request failed: '.($response['error']['message'] ?? $response->body())
            );
        }
    }

    /**
     * Extract the embedding from the response
     *
     * @return array<int, float>
     *
     * @throws RuntimeException
     */
    protected static function extractEmbedding(Response $response): array
    {
        $embedding = $response->json('embedding.values');

        if (empty($embedding)) {
            throw new RuntimeException(
                'No embedding found in Gemini response: '.$response->body()
            );
        }

        return $embedding;
    }
}
