<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('user_id');
            $table->unsignedBigInteger('parent_id')->nullable()->after('master_id');
            $table->foreign('parent_id')->references('id')->on('reviews')->cascadeOnDelete();
            $table->integer('rating')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['guest_name', 'parent_id']);
            $table->integer('rating')->nullable(false)->change();
        });
    }
};
