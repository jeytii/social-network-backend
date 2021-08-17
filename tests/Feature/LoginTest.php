<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
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
}
