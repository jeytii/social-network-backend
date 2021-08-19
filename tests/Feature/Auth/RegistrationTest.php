<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private $requiredFields = ['name', 'email_address', 'username', 'gender', 'password'];
    
    /**
     * Generate a JSON response.
     *
     * @param $body  array
     * @return object
     */
    private function getResponse($body = []): object
    {
        return $this->post(
            '/register',
            $body,
            ['Accept' => 'application/json']
        );
    }

    public function testErrorIfAllInputsAreNotSet()
    {
        $response = $this->getResponse();

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($this->requiredFields);
    }

    public function testErrorIfSomeInputsAreNotSet()
    {
        $response = $this->getResponse([
            'name' => 'John Doe',
            'password' => 'password'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email_address', 'username', 'gender' ]);
    }

    public function testErrorIfPasswordIsNotConfirmed()
    {
        $response = $this->getResponse([
            'password' => 'User@123',
            'password_confirmation' => 'asdasdasd'
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.password', ['The password confirmation does not match.']);
    }

    public function testErrorIfValueLengthsAreLessThanMinimum()
    {
        $response = $this->getResponse([
            'name' => 'z',
            'username' => 'abc',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.name', ['The name must be at least 2 characters long.']);
        $response->assertJsonPath('errors.username', ['The username must be 6 to 20 characters long.']);
        $response->assertJsonPath(
            'errors.password',
            ['The password must be at least 8 characters long and have one uppercase letter, number, and special character.']
        );
    }

    public function testErrorIfValueLengthsAreInRangeBetweenRequiredLengths()
    {
        $response = $this->getResponse([
            'username' => 'thebigbrownfoxjumpsoverthelazydog',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.username', ['The username must be 6 to 20 characters long.']);
    }

    public function testErrorIfEnteredValuesAlreadyExist()
    {
        $user = DB::table('users')->first();

        $response = $this->getResponse([
            'email_address' => $user->email_address,
            'username' => $user->username,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email_address', ['Oops! email address already exists.']);
        $response->assertJsonPath('errors.username', ['Oops! username already exists.']);
    }
    
    public function testErrorIfValuesDoNotMatchWithRegexPatterns()
    {
        $response = $this->getResponse([
            'username' => 'u$ername@123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.username', ['Only letters, numbers, dots, and underscores are allowed.']);
    }

    public function testSuccessfulRegistration()
    {
        $user = User::factory()->make();
        $body = collect($user->only($this->requiredFields));

        Event::fake([Registered::class]);
        
        $response = $this->getResponse(
            $body->merge(['password_confirmation' => $user->password])->toArray()
        );

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'username' => $user->username,
            'email_address' => $user->email_address,
        ]);

        Event::assertDispatched(fn(Registered $event) => $event->user->username === $user->username);
        Event::assertListening(Registered::class, SendEmailVerificationNotification::class);
    }

    public static function tearDownAfterClass(): void
    {
        (new self())->setUp();
        DB::table('users')->truncate();
    }
}
