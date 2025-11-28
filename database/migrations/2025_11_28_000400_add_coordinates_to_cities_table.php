<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->double('latitude')->nullable()->after('name');
            $table->double('longitude')->nullable()->after('latitude');
        });

        $cities = [
            ['name' => 'Вінниця', 'latitude' => 49.233083, 'longitude' => 28.468217],
            ['name' => 'Дніпро', 'latitude' => 48.464717, 'longitude' => 35.046183],
            ['name' => 'Донецьк', 'latitude' => 48.015883, 'longitude' => 37.802850],
            ['name' => 'Житомир', 'latitude' => 50.254650, 'longitude' => 28.657850],
            ['name' => 'Запоріжжя', 'latitude' => 47.838800, 'longitude' => 35.139567],
            ['name' => 'Івано-Франківськ', 'latitude' => 48.922633, 'longitude' => 24.711117],
            ['name' => 'Київ', 'latitude' => 50.450100, 'longitude' => 30.523399],
            ['name' => 'Кропивницький', 'latitude' => 48.507933, 'longitude' => 32.262317],
            ['name' => 'Луганськ', 'latitude' => 48.574041, 'longitude' => 39.307815],
            ['name' => 'Луцьк', 'latitude' => 50.747232, 'longitude' => 25.325383],
            ['name' => 'Львів', 'latitude' => 49.839684, 'longitude' => 24.029717],
            ['name' => 'Миколаїв', 'latitude' => 46.975033, 'longitude' => 31.994583],
            ['name' => 'Одеса', 'latitude' => 46.482526, 'longitude' => 30.723309],
            ['name' => 'Полтава', 'latitude' => 49.588267, 'longitude' => 34.551417],
            ['name' => 'Рівне', 'latitude' => 50.619553, 'longitude' => 26.251617],
            ['name' => 'Суми', 'latitude' => 50.907700, 'longitude' => 34.798100],
            ['name' => 'Тернопіль', 'latitude' => 49.553516, 'longitude' => 25.594767],
            ['name' => 'Ужгород', 'latitude' => 48.620799, 'longitude' => 22.287883],
            ['name' => 'Харків', 'latitude' => 49.993500, 'longitude' => 36.230383],
            ['name' => 'Херсон', 'latitude' => 46.635417, 'longitude' => 32.616867],
            ['name' => 'Хмельницький', 'latitude' => 49.422983, 'longitude' => 26.987133],
            ['name' => 'Черкаси', 'latitude' => 49.444433, 'longitude' => 32.057083],
            ['name' => 'Чернівці', 'latitude' => 48.292068, 'longitude' => 25.935837],
            ['name' => 'Чернігів', 'latitude' => 51.498200, 'longitude' => 31.289350],
            ['name' => 'Сімферополь', 'latitude' => 44.952118, 'longitude' => 34.102417],
        ];

        foreach ($cities as $city) {
            $existing = DB::table('cities')->where('name', $city['name'])->first();
            if ($existing) {
                DB::table('cities')
                    ->where('id', $existing->id)
                    ->update([
                        'latitude' => $city['latitude'],
                        'longitude' => $city['longitude'],
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('cities')->insert([
                    'name' => $city['name'],
                    'latitude' => $city['latitude'],
                    'longitude' => $city['longitude'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};

