<?php

namespace BenBjurstrom\SqliteVecScout\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BenBjurstrom\SqliteVecScout\SqliteVecScout
 */
class SqliteVecScout extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BenBjurstrom\SqliteVecScout\SqliteVecScout::class;
    }
}
