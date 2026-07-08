<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A master ↔ user relationship is assumed to be 1:1 throughout the app
     * (`Master::where('user_id', $user->id)->first()`), but the column has
     * never had a uniqueness constraint. Before adding the master web portal
     * (a second login surface hitting the same lookup), close that gap.
     *
     * Defensively nullify duplicate user_id values first (keeping the oldest
     * master row per user) so the unique index can be added safely even if
     * a dev/staging DB already has violating rows. NULLs are unaffected by
     * a unique index in both MySQL and SQLite.
     */
    public function up(): void
    {
        $duplicateUserIds = DB::table('masters')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $ids = DB::table('masters')
                ->where('user_id', $userId)
                ->orderBy('id')
                ->pluck('id');

            // Keep the first (oldest) master claimed by this user, null out the rest.
            $idsToClear = $ids->slice(1);

            if ($idsToClear->isNotEmpty()) {
                DB::table('masters')
                    ->whereIn('id', $idsToClear)
                    ->update(['user_id' => null]);
            }
        }

        Schema::table('masters', function (Blueprint $table) {
            $table->unique('user_id', 'masters_user_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropUnique('masters_user_id_unique');
        });
    }
};
