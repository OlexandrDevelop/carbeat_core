<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair requests are broadcast to every master offering the matching
 * service (App\Http\Services\RepairRequestNotificationService), not owned by
 * one — so "called" / "rejected" is per (master, repair_request) pair, not a
 * column on repair_requests itself. A missing row means "new" (never acted
 * on) — rows are only written once a master takes an action.
 *
 * Idempotent on purpose: on first deploy this failed production mid-way —
 * `Schema::create()` compiled the unique constraint as a trailing `ALTER
 * TABLE ... ADD UNIQUE` (separate from the CREATE TABLE itself, because of
 * the two constrained() foreign keys ahead of it), and MySQL rejected it
 * with "Identifier name ... is too long" (65 chars, over the 64-char limit)
 * — Laravel's default auto-generated name for a 2-column unique constraint
 * on this table name. That left the table created but without the unique
 * index, and blocked every migration after this one from ever running.
 * Re-running this version (with a short explicit index name) must finish
 * the job from whatever partial state a given environment is in, without
 * needing manual DB surgery.
 */
return new class extends Migration
{
    private const UNIQUE_INDEX = 'mrrs_master_repair_request_unique';

    public function up(): void
    {
        if (! Schema::hasTable('master_repair_request_statuses')) {
            Schema::create('master_repair_request_statuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('master_id')->constrained()->cascadeOnDelete();
                $table->foreignId('repair_request_id')->constrained()->cascadeOnDelete();
                $table->enum('status', ['pending', 'called', 'rejected'])->default('pending');
                $table->timestamp('called_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();
            });
        }

        $hasUniqueIndex = collect(Schema::getIndexes('master_repair_request_statuses'))
            ->contains(fn (array $index) => $index['name'] === self::UNIQUE_INDEX);

        if (! $hasUniqueIndex) {
            Schema::table('master_repair_request_statuses', function (Blueprint $table) {
                $table->unique(['master_id', 'repair_request_id'], self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_repair_request_statuses');
    }
};
