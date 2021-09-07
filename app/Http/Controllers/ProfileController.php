<?php

namespace App\Http\Controllers;

use App\Models\{User, Post};
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProfileController extends Controller
{
    /**
     * Get the paginated list of followers or followed users.
     *
     * @param \App\Models\User  $user
     * @param string  $type
     * @return array
     */
    private function getConnections(User $user, string $type)
    {
        return $user->{$type}()->withPaginated(20, [
            'slug', 'name', 'username', 'gender', 'image_url'
        ]);
    }

    /**
     * Get the paginated list of user's own posts.
     *
     * @param \App\Models\User  $user
     * @return array
     */
    private function getOwnPosts(User $user)
    {
        return $user->posts()
                ->withUser()
                ->withCount(['likers as likes_count', 'comments'])
                ->orderByDesc('created_at')
                ->withPaginated();
    }

    /**
     * Get the paginated list of user's likes or bookmarks.
     *
     * @param \App\Models\User  $user
     * @param string  $type
     * @return array
     */
    private function getLikesOrBookmarks(User $user, string $type)
    {
        return $user->{$type}()
                ->withUser()
                ->withCount(['likers as likes_count', 'comments'])
                ->orderByPivot('created_at', 'desc')
                ->withPaginated();
    }

    /**
     * Get the paginated list of user's comments on posts.
     *
     * @param int  $userId
     * @return array
     */
    private function getCommentsOnPosts($userId)
    {
        return Post::whereHas('comments', fn($q) => $q->where('user_id', $userId))
                    ->withUser()
                    ->with([
                        'comments' => fn($q) => $q->withUser()->orderByDesc('created_at')
                    ])
                    ->withCount(['likers as likes_count', 'comments'])
                    ->withPaginated();
    }

    /**
     * Get all posts that the user owns and interacted with.
     *
     * @param string  $username
     * @param string  $section
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function get(string $username, string $section)
    {
        try {
            $user = User::where('username', $username)->firstOrFail();
            
            if (in_array($section, ['followers', 'following'])) {
                $data = $this->getConnections($user, $section);
            }

            if ($section === 'posts') {
                $data = $this->getOwnPosts($user);
            }
            
            if ($section === 'comments') {
                $data = $this->getCommentsOnPosts($user->id);
            }

            if (in_array($section, ['likes', 'bookmarks'])) {
                $data = $this->getLikesOrBookmarks($user, $section);
            }

            return response()->json($data);
        }
        catch (ModelNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Get the user's profile info.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getInfo(string $username)
    {
        try {
            $data = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();

            return response()->json([
                'data' => $data->append('full_birth_date')
            ]);
        }
        catch (ModelNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Update the auth user's profile info.
     *
     * @param \App\Http\Requests\UpdateUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request)
    {   
        $body = $request->user()->no_birthdate ?
                $request->validated() :
                $request->only(['name', 'location', 'bio', 'image_url']);

        $request->user()->update($body);

        return response()->json(['updated' => true]);
    }
}
