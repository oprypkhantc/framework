<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class EloquentTransactionWithAfterCommitUsingDatabaseTransactionsTest extends TestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use DatabaseTransactions;

    /**
     * The current database driver.
     *
     * @return string
     */
    protected $driver;

    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            foreach (array_keys($this->app['db']->getConnections()) as $name) {
                $this->app['db']->purge($name);
            }
        });

        parent::setUp();

        if ($this->usesSqliteInMemoryDatabaseConnection()) {
            $this->markTestSkipped('Test cannot be used with in-memory SQLite connection.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $connection = $app->make('config')->get('database.default');

        $this->driver = $app['config']->get("database.connections.$connection.driver");
    }

    public function testFailsWhenStartedTransactionIsCommittedOrRolledBack(): void
    {
        // Push the callback to the end of the array so it's called after DatabaseTransactions
        $this->beforeApplicationDestroyedCallbacks[] = function () {
            self::assertNotNull($this->callbackException);
            self::assertSame('Transaction started by DatabaseTransactions was committed or rolled back. Have you missed a DB::commit() or a DB::rollBack()?', $this->callbackException->getMessage());


            $this->callbackException = null;
        };

        self::assertSame(1, DB::transactionLevel());

        // Imitate a forgotten DB::commit() inside of a code that's being tested.
        DB::commit();
    }
}
