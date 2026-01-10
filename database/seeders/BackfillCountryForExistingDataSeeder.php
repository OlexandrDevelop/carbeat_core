<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillCountryForExistingDataSeeder extends Seeder
{
    public function run(): void
    {
        $countryId = DB::table('countries')->where('code', 'UA')->value('id');
        if (!$countryId) {
            $this->command->error('UA country not found. Run CountriesTableSeeder first.');
            return;
        }

        $tables = ['cities','services','masters','clients','users'];
        foreach ($tables as $table) {
            DB::table($table)->whereNull('country_id')->update(['country_id' => $countryId]);
        }

        $this->command->info('Backfilled country_id to existing rows.');
    }
}

