<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally fails: column 'name' already exists on masters table
        Schema::table('masters', function (Blueprint $table) {
            $table->string('name');
        });
    }

    public function down(): void {}
};
