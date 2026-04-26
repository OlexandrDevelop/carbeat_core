<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->string('status')->nullable()->after('working_hours');
            $table->timestamp('status_expires_at')->nullable()->after('status');
            $table->index(['status', 'status_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropIndex(['status', 'status_expires_at']);
            $table->dropColumn(['status', 'status_expires_at']);
        });
    }
};
