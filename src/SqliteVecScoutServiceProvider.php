<?php

namespace BenBjurstrom\SqliteVecScout;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use RuntimeException;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;

class SqliteVecScoutServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('sqlite-vec-scout')
            ->hasConfigFile('sqlite-vec-scout')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('benbjurstrom/sqlite-vec-scout');
            });
    }

    public function boot(): void
    {
        parent::boot();

        $this->ensureSqliteVecExtensionIsLoaded();

        resolve(EngineManager::class)->extend('sqlite-vec', function () {
            return new SqliteVecEngine;
        });
    }

    protected function ensureSqliteVecExtensionIsLoaded(): void
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        try {
            $connection = DB::connection();

            if ($connection->getDriverName() !== 'sqlite') {
                return;
            }

            if ($this->vecModuleAvailable($connection)) {
                return;
            }

            $this->attemptToLoadVecExtension($connection);

            if ($this->vecModuleAvailable($connection)) {
                return;
            }

            $this->throwMissingExtensionException($connection);
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException(
                'Unable to verify sqlite-vec availability: '.$exception->getMessage(),
                previous: $exception
            );
        }
    }

    protected function vecModuleAvailable(ConnectionInterface $connection): bool
    {
        $moduleRow = $connection->selectOne(
            "select count(*) as aggregate from pragma_module_list where name = 'vec0'"
        );

        $count = $moduleRow instanceof \stdClass
            ? (int) $moduleRow->aggregate
            : (int) ($moduleRow['aggregate'] ?? 0);

        return $count > 0;
    }

    protected function attemptToLoadVecExtension(ConnectionInterface $connection): void
    {
        $extensionPath = (string) config('sqlite-vec-scout.extension_path', '');

        if ($extensionPath === '') {
            return;
        }

        /** @var \PDO $pdo */
        $pdo = $connection->getPdo(); // @phpstan-ignore method.notFound

        if (! method_exists($pdo, 'loadExtension')) {
            throw new RuntimeException(
                'PDO::loadExtension is disabled on this PHP build. Compile sqlite-vec directly into PHP using static-php-cli as described at https://benbjurstrom.com/sqlite-vec-php.'
            );
        }

        try {
            $pdo->loadExtension($extensionPath);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Failed to load sqlite-vec extension from [{$extensionPath}]: ".$exception->getMessage(),
                previous: $exception
            );
        }
    }

    protected function throwMissingExtensionException(ConnectionInterface $connection): void
    {
        $loadExtensionEnabled = $this->extensionLoadingEnabled($connection);

        $message = 'The sqlite-vec extension is not installed on the active SQLite connection. ';

        if ($loadExtensionEnabled === false) {
            $message .= 'This PHP binary cannot load SQLite extensions at runtime. '
                .'Compile sqlite-vec directly into PHP with static-php-cli by following https://benbjurstrom.com/sqlite-vec-php.';
        } else {
            $message .= 'Provide a compiled vec0 extension and set its path via the sqlite-vec-scout.extension_path config option, '
                .'or load it before Laravel boots. Build instructions are available at https://benbjurstrom.com/sqlite-vec-php.';
        }

        throw new RuntimeException($message);
    }

    protected function extensionLoadingEnabled(ConnectionInterface $connection): bool
    {
        $compileRow = $connection->selectOne(
            "select sqlite_compileoption_used('ENABLE_LOAD_EXTENSION') as enabled"
        );

        $enabled = $compileRow instanceof \stdClass
            ? (int) $compileRow->enabled
            : (int) ($compileRow['enabled'] ?? 0);

        return $enabled === 1;
    }
}
