<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->updateOrInsert(
            ['code' => 'UA'],
            [
                'name' => 'Ukraine',
                'phone_code' => '+380',
                'currency' => 'UAH',
                'locale' => 'uk_UA',
                'timezone' => 'Europe/Kiev',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

