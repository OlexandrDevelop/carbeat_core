<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tariffs')) {
            Schema::dropIfExists('tariffs');
        }
    }

    public function down(): void
    {
        // Intentionally left empty: tariffs table is deprecated
    }
};


