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
        User::factory(50)->hasPosts(30)->create();
    }
}
