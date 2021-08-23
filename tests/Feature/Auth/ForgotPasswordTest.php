<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Generate forgot password response.
     *
     * @param string|null  $email
     * @return \Illuminate\Testing\TestResponse
     */
    private function getResponse(?string $email = null)
    {
        return $this->post(
            '/forgot-password',
            ['email' => $email],
            ['Accept' => 'application/json']
        );
    }

    public function testErrorIfEmailAddressIsNotSet()
    {
        Notification::fake();

        $response = $this->getResponse();

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        Notification::assertNothingSent();
    }

    public function testErrorIfEmailAddressIsInvalid()
    {
        Notification::fake();

        $response = $this->getResponse('invalidemailaddress');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email', ['The email must be a valid email address.']);
        Notification::assertNothingSent();
    }

    public function testErrorIfEmailAddressDoesNotExist()
    {
        Notification::fake();

        $response = $this->getResponse('dummy@email.com');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email', ["We can't find a user with that email address."]);
        Notification::assertNothingSent();
    }

    public function testSuccessfullySentPasswordResetRequest()
    {
        Notification::fake();

        $user = User::first();
        $response = $this->getResponse($user->email);

        $response->assertOk();
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public static function tearDownAfterClass(): void
    {
        (new self())->setUp();
        DB::table('users')->truncate();
        DB::table('password_resets')->truncate();
    }
}
