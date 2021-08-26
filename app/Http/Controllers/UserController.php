<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * Get the necessary columns for basic data.
     *
     * @var array
     */
    private $basic_columns = ['slug', 'name', 'username', 'gender', 'image_url'];

    /**
     * Get paginated list of user models.
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        // Format each user model with only the necessary columns.
        $data = User::where('id', '!=', auth()->id())
                ->whereDoesntHave('followers', fn($query) => $query->where('id', auth()->id()))
                ->paginate(20, $this->basic_columns);
        
        // If there are still remaining items.
        $hasMore = $data->hasMorePages();

        // Increment the current offset/page by 1 if there are still more items left.
        $nextOffset = $data->hasMorePages() ? $data->currentPage() + 1 : null;

        return response()->json([
            'data' => $data->items(),
            'has_more' => $hasMore,
            'next_offset' => $nextOffset,
        ]);
    }

    /**
     * Get suggested user models.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSuggested()
    {
        // Get 3 random users with basic data.
        $data = DB::table('users')
                ->inRandomOrder()
                ->limit(3)
                ->get($this->basic_columns);

        return response()->json(compact('data'));
    }

    /**
     * Get the paginated list of followers or followed users.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getConnections(Request $request)
    {
        $type = $request->query('type');

        abort_if(
            is_null($type) || !in_array($type, ['following', 'followers']),
            404
        );

        $data = $request->user()
                ->{$type}()
                ->paginate(20, $this->basic_columns)
                ->items();

        return response()->json(compact('data'));
    }

    /**
     * Get 3 suggested user models.
     *
     * @param App\Http\Requests\UpdateUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request)
    {
        $this->authorize('update', $request->user());
        
        if (
            is_null($request->user()->birth_month) &&
            is_null($request->user()->birth_day) &&
            is_null($request->user()->birth_year)
        ) {
            $request->user()->update($request->all());
        }
        else {
            $request->user()->update($request->only([
                'name', 'username', 'location', 'bio', 'image_url'
            ]));
        }

        return response()->json(['updated' => true]);
    }

    /**
     * Add a user to the list of followed users.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function follow(Request $request, User $user)
    {
        $this->authorize('follow', $user);
        
        $request->user()->following()->sync([$user->id]);

        return response()->json(['followed' => true]);
    }

    /**
     * Add a user to the list of followed users.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function unfollow(Request $request, User $user)
    {
        $this->authorize('unfollow', $user);

        $request->user()->following()->detach($user->id);

        return response()->json(['unfollowed' => true]);
    }
}
