<?php

namespace BenBjurstrom\SqliteVecScout\Commands;

use Illuminate\Console\Command;

class SqliteVecScoutCommand extends Command
{
    public $signature = 'sqlite-vec-scout';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
