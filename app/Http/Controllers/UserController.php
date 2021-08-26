<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get the necessary columns for basic data.
     *
     * @var array
     */
    private $basic_columns = ['slug', 'name', 'username', 'gender', 'image_url'];

    /**
     * Get 20 paginated user models.
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        // Format each user model with only the necessary columns.
        $data = User::select($this->basic_columns)->paginate(20);
        
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
     * Get 3 suggested user models.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSuggested()
    {
        // Get 3 random users with basic data.
        $data = User::inRandomOrder()->limit(3)->get($this->basic_columns);

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
            User::where('id', $request->user()->id)->update($request->all());
        }
        else {
            User::where('id', $request->user()->id)->update($request->only([
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
