<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Auth\Notifications\ResetPassword;

class ForgotPasswordTest extends TestCase
{
    /**
     * Generate forgot password response.
     *
     * @param string|null  $email
     * @return \Illuminate\Testing\TestResponse
     */
    private function jsonResponse(?string $email = null)
    {
        return $this->postJson('/forgot-password', ['email' => $email]);
    }

    public function testErrorIfEmailAddressIsNotSet()
    {
        Notification::fake();

        $response = $this->jsonResponse();

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        Notification::assertNothingSent();
    }

    public function testErrorIfEmailAddressIsInvalid()
    {
        Notification::fake();

        $response = $this->jsonResponse('invalidemailaddress');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email', ['The email must be a valid email address.']);
        Notification::assertNothingSent();
    }

    public function testErrorIfEmailAddressDoesNotExist()
    {
        Notification::fake();

        $response = $this->jsonResponse('dummy@email.com');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email', ["We can't find a user with that email address."]);
        Notification::assertNothingSent();
    }

    public function testSuccessfullySentPasswordResetRequest()
    {
        $user = User::factory()->create();

        Notification::fake();

        $response = $this->jsonResponse($user->email);

        $response->assertOk();
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
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
        DB::table('password_resets')->truncate();
    }
}
