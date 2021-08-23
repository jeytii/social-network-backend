<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Event, Hash, Notification};

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Generate reset password response.
     *
     * @param array  $body
     * @return \Illuminate\Testing\TestResponse
     */
    private function getResponse(array $body = [])
    {
        return $this->post(
            '/reset-password',
            $body,
            ['Accept' => 'application/json']
        );
    }

    public function testErrorIfAllInputsAreNotSet()
    {
        Event::fake();

        $response = $this->getResponse();

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password', 'token']);
        Event::assertNotDispatched(PasswordReset::class);
    }

    public function testSuccessfulPasswordReset()
    {
        Notification::fake();

        $emailAddress = DB::table('users')->first()->email;
        $token = Str::random(64);
        $hash = Hash::make($token);
        
        $forgotPasswordResponse = $this->post(
            '/forgot-password',
            ['email' => $emailAddress],
            ['Accept' => 'application/json']
        );

        $passwordReset = DB::table('password_resets')->where('email', $emailAddress);
        
        $passwordReset->update([
            'token' => Hash::make($token)
        ]);
        
        $forgotPasswordResponse->assertOk();
        
        Event::fake([ PasswordReset::class ]);
        
        $resetPasswordResponse = $this->getResponse([
            'email' => $passwordReset->first()->email,
            'password' => 'P@ssword123',
            'password_confirmation' => 'P@ssword123',
            'token' => $token,
        ]);

        $resetPasswordResponse->assertOk();

        // $user = DB::table('users')->where('email', $passwordReset->email)->first();

        // $this->assertTrue(Hash::check('P@ssword12345', $user->password));
        // Event::assertDispatched(fn(PasswordReset $event) => $event->user->id === $user->id);
    }

    public static function tearDownAfterClass(): void
    {
        (new self())->setUp();
        DB::table('users')->truncate();
        DB::table('password_resets')->truncate();
    }
}
