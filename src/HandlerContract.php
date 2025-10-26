<?php

namespace BenBjurstrom\SqliteVecScout;

interface HandlerContract
{
    /**
     * Generate an embedding vector for the given input
     *
     * @param  string  $input  The text to generate an embedding for
     * @param  IndexConfig  $config  The handler configuration
     * @return Vector The generated embedding vector
     *
     * @throws \RuntimeException If the embedding generation fails
     */
    public static function handle(string $input, IndexConfig $config): Vector;
}
