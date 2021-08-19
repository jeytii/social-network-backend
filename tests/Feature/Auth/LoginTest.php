<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    
    /**
     * Generate a JSON response.
     *
     * @param $body  array
     * @return object
     */
    private function getResponse(array $body = []): object
    {
        return $this->post(
            '/api/login',
            $body,
            ['Accept' => 'application/json']
        );
    }

    public function testErrorIfBothAreNotSet()
    {
        $response = $this->getResponse();

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'password']);
    }

    public function testErrorIfUsernameIsNotSet()
    {
        $response = $this->getResponse([
            'password' => 'password'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username']);
    }

    public function testErrorIfPasswordIsNotSet()
    {
        $response = $this->getResponse([
            'username' => 'username'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function testErrorIfCredentialsDoNotExist()
    {
        $response = $this->getResponse([
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

        $response = $this->getResponse([
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
        $user = DB::table('users')->whereNotNull('email_verified_at')->get()->random();

        $response = $this->getResponse([
            'username' => $user->username,
            'password' => 'P@ssword123'
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    public static function tearDownAfterClass(): void
    {
        (new self())->setUp();
        DB::table('users')->truncate();
    }
}
