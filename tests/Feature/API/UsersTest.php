<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seeds a particular number of fake user models.
     * Check out database/seeders/DatabaseSeeder.php
     *
     * @var boolean
     */
    protected $seed = true;

    /**
     * Make an execution before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(
            User::first(),
            ['*'],
        );
    }

    public function testGetPaginatedUsers()
    {
        // First scroll to the bottom
        $response = $this->get('/api/users?page=1');

        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('has_more', true);
        $response->assertJsonPath('next_offset', 2);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['slug', 'name', 'username', 'gender', 'image_url'],
            ],
        ]);

        // Second scroll to the bottom
        $response = $this->get('/api/users?page=2');

        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('has_more', true);
        $response->assertJsonPath('next_offset', 3);

        // Third scroll to the bottom
        $response = $this->get('/api/users?page=3');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('has_more', false);
        $response->assertJsonPath('next_offset', null);

        // Fourth scroll to the bottom (Data should be empty)
        $response = $this->get('/api/users?page=4');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('has_more', false);
        $response->assertJsonPath('next_offset', null);
    }

    public function testShouldReturnThreeSuggestedUsers()
    {
        $response = $this->get('/api/users/suggested');

        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['slug', 'name', 'username', 'gender', 'image_url'],
            ],
        ]);
    }

    /**
     * Make an execution after all tests.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        (new self())->setUp();
        DB::table('users')->truncate();
    }
}
