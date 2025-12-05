<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->boolean('is_claimed')->default(false)->after('premium_until');
            $table->string('claim_token', 64)->nullable()->unique()->after('is_claimed');
            $table->timestamp('phone_verified_at')->nullable()->after('claim_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropColumn(['is_claimed', 'claim_token', 'phone_verified_at']);
        });
    }
};
