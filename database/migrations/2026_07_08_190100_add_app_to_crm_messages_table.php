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
     * CrmMessage is the only CRM model without brand scoping (`app` column +
     * AppScoped trait) — a latent multi-tenancy bug that becomes a real data
     * leak once a second client (the master web portal) can also read CRM
     * chat data. Add the column, backfill it from the parent chat thread's
     * `app` value, then the model gets the AppScoped trait in the same
     * commit.
     */
    public function up(): void
    {
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->string('app', 50)->default('carbeat')->after('thread_uuid');
        });

        DB::statement(
            'UPDATE crm_messages
             SET app = (
                 SELECT crm_chat_threads.app
                 FROM crm_chat_threads
                 WHERE crm_chat_threads.uuid = crm_messages.thread_uuid
             )
             WHERE EXISTS (
                 SELECT 1 FROM crm_chat_threads WHERE crm_chat_threads.uuid = crm_messages.thread_uuid
             )'
        );

        Schema::table('crm_messages', function (Blueprint $table) {
            $table->index(['thread_uuid', 'app'], 'crm_messages_thread_app_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->dropIndex('crm_messages_thread_app_idx');
            $table->dropColumn('app');
        });
    }
};
