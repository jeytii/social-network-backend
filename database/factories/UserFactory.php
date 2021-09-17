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

    private function dotCase(string $string, string $separator): string
    {
        return str_replace(['. ', ' '], $separator, strtolower($string));
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $gender = $this->faker->randomElement(['Male', 'Female']);
        $name = $this->faker->name(strtolower($gender));
        $username = join('', $this->faker->words());
        $phoneNumber = (string) random_int(100000000, 999999999);
        $date = Carbon::parse($this->faker->date());

        return [
            'name' => $name,
            'email' => $this->faker->safeEmail(),
            'username' => $username,
            'phone_number' => "9{$phoneNumber}",
            'gender' => $gender,
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
