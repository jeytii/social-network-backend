<?php

namespace Database\Factories;

use App\Models\{User, Notification};
use App\Notifications\NotifyUponAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $gender = $this->faker->randomElement(['Male', 'Female']);

        return [
            'notifiable_id' => User::factory(),
            'notifiable_type' => User::class,
            'type' => NotifyUponAction::class,
            'data' => json_encode([
                'action' => rand(1, 4),
                'is_read' => false,
                'url' => '/sample/url',
                'user' => [
                    'name' => $this->faker->name($gender),
                    'gender' => $gender,
                    'image_url' => null,
                ],
            ]),
        ];
    }

}
