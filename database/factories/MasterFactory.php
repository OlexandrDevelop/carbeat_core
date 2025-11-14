<?php

namespace Database\Factories;

use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class MasterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $password = bcrypt('werter');
        // Diapason of central Europe
        $latitude = fake()->randomFloat(6, 46, 54);
        $longitude = fake()->randomFloat(6, 6, 19);

        return [
            'name' => fake()->name(),
            'contact_phone' => fake()->unique()->phoneNumber(),
            'age' => fake()->numberBetween(18, 80),
            'service_id' => fake()->numberBetween(1, 5),
            'is_premium' => false,
            'premium_until' => null,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'description' => fake()->text(200),
            'address' => fake()->address(),
            'photo' => $this->getImageUrl(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Master $master) {
            // create from 3 to 5 services for master
            $services = Service::inRandomOrder()->limit(rand(3, 5))->get();
            $master->services()->attach($services);

            $mainService = $services->random();
            $user = User::factory()->create();
            $user->name = $master->name;
            $user->save();
            $master->update([
                'service_id' => $mainService->id,
                'user_id' => $user->id,
            ]);
        });
    }

    public static function getImageUrl(): string
    {
        // Pull only direct files from 'public' disk (no recursion needed here)
        $files = Storage::files('public');

        // If no files exist, return a placeholder path – caller should ensure such file exists
        if (empty($files)) {
            return 'defaults/avatar.png';
        }

        // Select a random file safely
        $randomFile = fake()->randomElement($files);

        return str_replace('public/', '', $randomFile);
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
