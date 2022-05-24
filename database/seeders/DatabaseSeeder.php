<?php

namespace Database\Seeders;

use App\Models\{User, Post};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::factory(50)
            ->has(Post::factory(30)->sequence(
                fn($sequence) => ['created_at' => now()->addSeconds($sequence->index)]
            ))
            ->create();
    }
}
