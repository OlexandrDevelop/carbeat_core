<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //запуск сідерів перед цим міграцією обов'язковий, виконуємо сідери
        $this->callSeeders();

        $tables = ['cities','services','masters','clients','users'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                // Set default where null to avoid issues is handled by seeder
                $t->unsignedBigInteger('country_id')->nullable(false)->change();
                $t->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        $tables = ['cities','services','masters','clients','users'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign([$table . '_country_id_foreign']);
                $t->unsignedBigInteger('country_id')->nullable()->change();
            });
        }
    }

    protected function callSeeders(): void
    {
        // Виклик сідерів для заповнення таблиці країн
        \Artisan::call('db:seed', [
            '--class' => 'CountriesTableSeeder',
            '--force' => true,
        ]);

        \Artisan::call('db:seed', [
            '--class' => 'BackfillCountryForExistingDataSeeder',
            '--force' => true,
        ]);
    }
};

