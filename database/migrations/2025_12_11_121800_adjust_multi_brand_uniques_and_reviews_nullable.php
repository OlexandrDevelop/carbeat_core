<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure `app` column exists on users/masters (safety in case previous migration wasn't applied)
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'app')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('app')->default('carbeat')->index()->after('id');
            });
        }
        if (Schema::hasTable('masters') && ! Schema::hasColumn('masters', 'app')) {
            Schema::table('masters', function (Blueprint $table) {
                $table->string('app')->default('carbeat')->index()->after('id');
            });
        }

        // USERS: drop global unique(phone) and ensure unique(phone, app)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Drop old unique if present
                if ($this->indexExists('users', 'users_phone_unique')) {
                    $table->dropUnique('users_phone_unique');
                }
            });
            // Create composite unique if missing
            if (! $this->indexExists('users', 'users_phone_app_unique')) {
                DB::statement('CREATE UNIQUE INDEX `users_phone_app_unique` ON `users` (`phone`, `app`)');
            }
        }

        // MASTERS: drop global unique(slug) and ensure unique(slug, app)
        if (Schema::hasTable('masters')) {
            Schema::table('masters', function (Blueprint $table) {
                if ($this->indexExists('masters', 'masters_slug_unique')) {
                    $table->dropUnique('masters_slug_unique');
                }
            });
            if (! $this->indexExists('masters', 'masters_slug_app_unique')) {
                DB::statement('CREATE UNIQUE INDEX `masters_slug_app_unique` ON `masters` (`slug`, `app`)');
            }
        }

        // REVIEWS: allow NULL user_id and set FK ON DELETE SET NULL
        if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'user_id')) {
            // Make column nullable
            Schema::table('reviews', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
            // Drop existing FK if any, then recreate with SET NULL
            $fkName = $this->findForeignKeyName('reviews', 'user_id');
            if ($fkName) {
                Schema::table('reviews', function (Blueprint $table) use ($fkName) {
                    $table->dropForeign($fkName);
                });
            }
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // REVIEWS: revert user_id NOT NULL and FK default (NO ACTION)
        if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'user_id')) {
            $fkName = $this->findForeignKeyName('reviews', 'user_id');
            if ($fkName) {
                Schema::table('reviews', function (Blueprint $table) use ($fkName) {
                    $table->dropForeign($fkName);
                });
            }
            Schema::table('reviews', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                // Restore FK without SET NULL (MySQL default NO ACTION)
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        // MASTERS: drop composite unique and restore global unique
        if (Schema::hasTable('masters')) {
            Schema::table('masters', function (Blueprint $table) {
                if ($this->indexExists('masters', 'masters_slug_app_unique')) {
                    $table->dropUnique('masters_slug_app_unique');
                }
            });
            if (! $this->indexExists('masters', 'masters_slug_unique')) {
                DB::statement('CREATE UNIQUE INDEX `masters_slug_unique` ON `masters` (`slug`)');
            }
        }

        // USERS: drop composite unique and restore global unique
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_phone_app_unique')) {
                    $table->dropUnique('users_phone_app_unique');
                }
            });
            if (! $this->indexExists('users', 'users_phone_unique')) {
                DB::statement('CREATE UNIQUE INDEX `users_phone_unique` ON `users` (`phone`)');
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $result = DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]);
        return ! empty($result);
    }

    private function findForeignKeyName(string $table, string $column): ?string
    {
        $dbName = DB::getDatabaseName();
        $sql = "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL";
        $row = DB::selectOne($sql, [$dbName, $table, $column]);
        return $row->CONSTRAINT_NAME ?? null;
    }
};
