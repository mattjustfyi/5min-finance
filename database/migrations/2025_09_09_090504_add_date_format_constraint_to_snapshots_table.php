<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support adding CHECK constraints to existing tables
        // We need to recreate the table with the constraint
        DB::transaction(function () {
            // Create new table with constraint
            DB::statement('
                CREATE TABLE snapshots_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    user_id INTEGER NOT NULL,
                    date DATE NOT NULL CHECK (date GLOB "[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]" AND length(date) = 10),
                    notes TEXT,
                    accounts TEXT NOT NULL,
                    goals TEXT NOT NULL,
                    debts TEXT NOT NULL,
                    deleted_at DATETIME,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ');
            
            // Copy data from old table
            DB::statement('INSERT INTO snapshots_new SELECT * FROM snapshots');
            
            // Drop old table
            DB::statement('DROP TABLE snapshots');
            
            // Rename new table
            DB::statement('ALTER TABLE snapshots_new RENAME TO snapshots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite doesn't support dropping CHECK constraints directly
        // Would need to recreate the table without the constraint
    }
};
