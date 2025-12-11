<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Drop global unique index on phone if it exists
                if ($this->indexExists('users', 'users_phone_unique')) {
                    $table->dropUnique('users_phone_unique');
                }
            });
        }
        // Note: composite unique on (phone, app) already exists per project setup.
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Restore single-column unique on phone if it was dropped
                if (! $this->indexExists('users', 'users_phone_unique')) {
                    $table->unique('phone', 'users_phone_unique');
                }
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $result = DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]);
        return ! empty($result);
    }
};
