<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $gender = $this->faker->randomElement(['Male', 'Female']);
        $date = Carbon::parse($this->faker->date());

        return [
            'name' => $this->faker->name(strtolower($gender)),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->unique()->userName(),
            'gender' => $gender,
            'location' => $this->faker->city(),
            'birth_month' => $date->monthName,
            'birth_day' => $date->day,
            'birth_year' => $date->year,
            'email_verified_at' => now(),
            'password' => '$2y$10$LZ3lEGrSDY7lDNzaWmHkJ.DimdflkD1oCN9XXBotJUZ1Wbbfv7wDS', // P@ssword123
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
