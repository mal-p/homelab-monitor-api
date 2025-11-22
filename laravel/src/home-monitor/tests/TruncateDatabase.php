<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Truncate database tables to avoid dropping schema created in init-db.sql
 */
trait TruncateDatabase
{
    use RefreshDatabase;

    protected $protectedTables = [
        'migrations',
    ];

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (!$this->usingInMemoryDatabase()) {
            $this->truncateTablesExcept($this->protectedTables);
            
            $this->artisan('migrate');
        }
    }

    protected function truncateTablesExcept(array $except)
    {
        $tables = DB::select("
            SELECT tablename 
            FROM pg_tables 
            WHERE schemaname = 'public' 
            AND tablename NOT IN ('" . implode("','", $except) . "')
        ");

        foreach ($tables as $table) {
            DB::statement("TRUNCATE TABLE {$table->tablename} CASCADE");
        }
    }
}