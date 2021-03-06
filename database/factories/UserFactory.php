<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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

        return [
            'name' => $name,
            'email' => $this->faker->safeEmail(),
            'username' => $username,
            'gender' => $gender,
            'birth_date' => $this->faker->date(),
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
