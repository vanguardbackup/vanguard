<?php

declare(strict_types=1);

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use RuntimeException;

abstract class DuskTestCase extends BaseTestCase
{
    use DatabaseMigrations;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    public function runDatabaseMigrations(): void
    {
        $this->dropAllTables();
        $this->artisan('migrate');
    }

    protected function dropAllTables(): void
    {
        $connection = DB::connection()->getDriverName();

        match ($connection) {
            'pgsql' => $this->dropPostgresTables(),
            'mysql' => $this->dropMySQLTables(),
            'sqlite' => $this->dropSQLiteTables(),
            default => throw new RuntimeException("Unsupported database driver: {$connection}"),
        };
    }

    protected function dropPostgresTables(): void
    {
        DB::statement("SET session_replication_role = 'replica';");

        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        foreach ($tables as $table) {
            DB::statement('DROP TABLE IF EXISTS "' . $table->tablename . '" CASCADE');
        }

        DB::statement("SET session_replication_role = 'origin';");
    }

    protected function dropMySQLTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $table_array = get_object_vars($table);
            DB::statement('DROP TABLE IF EXISTS `' . $table_array[key($table_array)] . '`');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function dropSQLiteTables(): void
    {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
        foreach ($tables as $table) {
            if ($table->name !== 'sqlite_sequence') {
                DB::statement('DROP TABLE IF EXISTS "' . $table->name . '"');
            }
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $chromeOptions = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $chromeOptions
            )
        );
    }
}
