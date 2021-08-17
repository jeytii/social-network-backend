<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    
    /**
     * Generate a JSON response.
     *
     * @return object
     */
    private function getResponse($body = []): object
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
        $user = User::get()->random();

        User::where('slug', $user->slug)->update([
            'email_verified_at' => null
        ]);
        
        $response = $this->getResponse([
            'username' => $user->username,
            'password' => 'password'
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
            'password' => 'password'
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }
}
