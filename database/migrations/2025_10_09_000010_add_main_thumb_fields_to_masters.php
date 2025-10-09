<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->boolean('main_thumb_generated')->default(false)->after('photo');
            $table->string('main_thumb_url')->nullable()->after('main_thumb_generated');
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropColumn(['main_thumb_generated', 'main_thumb_url']);
        });
    }
};


