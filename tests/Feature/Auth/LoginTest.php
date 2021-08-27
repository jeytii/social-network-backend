<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class LoginTest extends TestCase
{   
    /**
     * Generate a JSON response from a POST request.
     *
     * @param $body  array
     * @return \Illuminate\Testing\TestResponse
     */
    private function jsonResponse(array $body = [])
    {
        return $this->postJson('/api/login', $body);
    }

    public function testErrorIfBothAreNotSet()
    {
        $response = $this->jsonResponse();

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'password']);
    }

    public function testErrorIfUsernameIsNotSet()
    {
        $response = $this->jsonResponse([
            'password' => 'password'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username']);
    }

    public function testErrorIfPasswordIsNotSet()
    {
        $response = $this->jsonResponse([
            'username' => 'username'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function testErrorIfCredentialsDoNotExist()
    {
        $response = $this->jsonResponse([
            'username' => 'username',
            'password' => 'password'
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot find username and password combination.'
        ]);
    }

    public function testErrorIfUserIsNotYetVerified()
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $response = $this->jsonResponse([
            'username' => $user->username,
            'password' => 'P@ssword123'
        ]);

        $response->assertUnauthorized();
        $response->assertJsonFragment([
            'message' => 'Your account is not yet verified.'
        ]);
    }

    public function testReturnATokenIfSucceeds()
    {
        $user = User::factory()->create();

        $response = $this->jsonResponse([
            'username' => $user->username,
            'password' => 'P@ssword123'
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
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
