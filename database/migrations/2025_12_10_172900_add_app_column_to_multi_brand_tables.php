<?php

use App\Enums\AppBrand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'clients',
            'masters',
            'services',
            'bookings',
            'reviews',
            'subscriptions',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'app')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->string('app')->default(AppBrand::CARBEAT)->index()->after('id');
                });
            }
        }

        // Example compound uniques per brand (adjust to real unique needs)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'phone') && ! $this->indexExists('users', 'users_phone_app_unique')) {
                    $table->unique(['phone', 'app'], 'users_phone_app_unique');
                }
            });
        }

        if (Schema::hasTable('masters')) {
            Schema::table('masters', function (Blueprint $table) {
                if (Schema::hasColumn('masters', 'slug') && ! $this->indexExists('masters', 'masters_slug_app_unique')) {
                    $table->unique(['slug', 'app'], 'masters_slug_app_unique');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'clients',
            'masters',
            'services',
            'bookings',
            'reviews',
            'subscriptions',
        ];

        // Drop unique constraints first
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_phone_app_unique')) {
                    $table->dropUnique('users_phone_app_unique');
                }
            });
        }

        if (Schema::hasTable('masters')) {
            Schema::table('masters', function (Blueprint $table) {
                if ($this->indexExists('masters', 'masters_slug_app_unique')) {
                    $table->dropUnique('masters_slug_app_unique');
                }
            });
        }

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'app')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['app']);
                    $table->dropColumn('app');
                });
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $result = DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]);
        return !empty($result);
    }
};
