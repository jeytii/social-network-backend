<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UsersTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Seeds 100 fake user models.
     * See database/seeders/DatabaseSeeder.php
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
        $response = $this->get('/api/users?page=5');

        $response->assertOk();
        $response->assertJsonCount(19, 'data');
        $response->assertJsonPath('has_more', false);
        $response->assertJsonPath('next_offset', null);

        // Fourth scroll to the bottom (Data should be empty)
        $response = $this->get('/api/users?page=6');

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

    public function testValidationErrorsInUpdatingProfile()
    {
        $user = User::first();

        $response = $this->actingAs($user)->putJson('/api/users/auth/update', [
            'birth_day' => 32,
            'bio' => $this->faker->paragraphs(5, true)
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.name', ['The name field is required.']);
        $response->assertJsonPath('errors.birth_month', ['The birth month field is required.']);
        $response->assertJsonPath('errors.birth_day', ['Birth day must be between 1 and 31 only.']);
        $response->assertJsonPath('errors.birth_year', ['The birth year field is required.']);
        $response->assertJsonPath('errors.bio', ['The number of characters exceeds the maximum length.']);
    }

    public function testCannotUpdateBirthDateThatIsNoLongerNull()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/users/auth/update', [
            'name' => $user->name,
            'birth_month' => 'January',
            'birth_day' => 12,
            'birth_year' => 1996,
        ]);

        $response->assertOk();
        
        $updatedUser = User::find($user->id);

        $this->assertTrue($user->full_birth_date === $updatedUser->full_birth_date);
    }

    public function testSuccessfullyUpdatedTheProfileInfo()
    {
        $user = User::factory()->create([
            'birth_month' => null,
            'birth_day' => null,
            'birth_year' => null,
        ]);

        $response = $this->actingAs($user)->putJson('/api/users/auth/update', [
            'name' => 'John Doe',
            'birth_month' => 'December',
            'birth_day' => 10,
            'birth_year' => 1990,
            'location' => 'Philippines',
            'bio' => 'Hello World',
        ]);

        $response->assertOk();
        
        $updatedUser = User::find($user->id);

        $this->assertTrue($updatedUser->name === 'John Doe');
        $this->assertTrue($updatedUser->full_birth_date === 'December 10, 1990');
        $this->assertTrue($updatedUser->location === 'Philippines');
        $this->assertTrue($updatedUser->bio === 'Hello World');
    }

    public function testCanFollowAUser()
    {
        $user = User::first();
        $userToFollow = User::find(2);

        $response = $this->actingAs($user)->postJson("/api/users/follow/{$userToFollow->slug}");

        $response->assertOk();
        $response->assertJson(['followed' => true]);
        $this->assertTrue((bool) $user->following()->find($userToFollow->id));
        $this->assertTrue((bool) $userToFollow->followers()->find($user->id));
    }
    
    public function testCanUnfollowAUser()
    {
        $user = User::first();
        $userToUnfollow = User::find(2);
        
        $response = $this->actingAs($user)->deleteJson("/api/users/unfollow/{$userToUnfollow->slug}");
        
        $response->assertOk();
        $response->assertJson(['unfollowed' => true]);
        $this->assertFalse((bool) $user->following()->find($userToUnfollow->id));
        $this->assertFalse((bool) $userToUnfollow->followers()->find($user->id));
    }

    public function testCannotUnfollowAUserThatIsNotFollowed()
    {
        $user = User::first();
        $userToUnfollow = User::find(2);

        $response = $this->actingAs($user)->deleteJson("/api/users/unfollow/{$userToUnfollow->slug}");

        $response->assertForbidden();
        $this->assertFalse((bool) $user->following()->find($userToUnfollow->id));
        $this->assertFalse((bool) $userToUnfollow->followers()->find($user->id));
    }

    public function testCannotFollowAUserThatIsAlreadyFollowed()
    {
        $user = User::first();
        $userToFollow = User::find(2);

        $firstCall = $this->actingAs($user)->postJson("/api/users/follow/{$userToFollow->slug}");
        $secondCall = $this->actingAs($user)->postJson("/api/users/follow/{$userToFollow->slug}");

        $firstCall->assertOk();
        $secondCall->assertForbidden();

        $this->assertTrue($user->following()->where('id', 2)->count() === 1);
        $this->assertTrue($userToFollow->followers()->where('id', 1)->count() === 1);
    }

    public function testErrorIfTypeQueryInConnectionsUrlIsBlank()
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/api/users/connections?page=1');

        $response->assertNotFound();
    }

    public function testErrorIfTypeQueryInConnectionsUrlIsNeitherFollowersNorFollowing()
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/api/users/connections?type=unknown&page=1');

        $response->assertNotFound();
    }

    public function testSuccessfullyGettingThePaginatedListOfFollowing()
    {
        $user = User::first();
        $user->following()->sync(range(11, 50));

        $firstCall = $this->actingAs($user)->get('/api/users/connections?type=following&page=1');

        $firstCall->assertOk();
        $firstCall->assertJsonCount(20, 'data');

        $secondCall = $this->actingAs($user)->get('/api/users/connections?type=following&page=2');

        $secondCall->assertOk();
        $secondCall->assertJsonCount(20, 'data');

        $thirdCall = $this->actingAs($user)->get('/api/users/connections?type=following&page=3');

        $thirdCall->assertOk();
        $thirdCall->assertJsonCount(0, 'data');
    }
    
    public function testSuccessfullyGettingThePaginatedListOfFollowers()
    {
        $user = User::find(2);
        $user->followers()->sync(range(31, 70));

        $firstCall = $this->actingAs($user)->get('/api/users/connections?type=followers&page=1');

        $firstCall->assertOk();
        $firstCall->assertJsonCount(20, 'data');

        $secondCall = $this->actingAs($user)->get('/api/users/connections?type=followers&page=2');

        $secondCall->assertOk();
        $secondCall->assertJsonCount(20, 'data');

        $thirdCall = $this->actingAs($user)->get('/api/users/connections?type=followers&page=3');

        $thirdCall->assertOk();
        $thirdCall->assertJsonCount(0, 'data');
    }

    public function testCannotFindAUserWithProvidedUsername()
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/api/users/foobar/profile');

        $response->assertNotFound();
    }

    public function testProfileInfoIsNotSelf()
    {
        $user = User::first();
        $visitedUsername = User::find(2)->username;

        $response = $this->actingAs($user)->get("/api/users/{$visitedUsername}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.is_self', false);
    }

    public function testProfileInfoIsSelf()
    {
        $user = User::first();

        $response = $this->actingAs($user)->get("/api/users/{$user->username}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.is_self', true);
    }

    public function testProfileInfoContainsNumberOfFollowersAndFollowing()
    {
        $user = User::first();

        $response = $this->actingAs($user)->get("/api/users/{$user->username}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.followers_count', 0);

        // Given the sync() method at testSuccessfullyGettingThePaginatedListOfFollowing()
        //that attached 40 models to its list of followed users.
        $response->assertJsonPath('data.following_count', 40);
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
