<?php

namespace BenBjurstrom\SqliteVecScout\Handlers;

use BenBjurstrom\SqliteVecScout\HandlerContract;
use BenBjurstrom\SqliteVecScout\IndexConfig;
use BenBjurstrom\SqliteVecScout\Vector;
use RuntimeException;

class FakeHandler implements HandlerContract
{
    /**
     * Get a Fake vector for a given input
     *
     * @throws RuntimeException
     */
    public static function handle(string $input, IndexConfig $config): Vector
    {
        return new Vector(array_fill(0, $config->dimensions, 0.1));
    }
}
