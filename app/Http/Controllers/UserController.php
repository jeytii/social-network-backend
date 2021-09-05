<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        // Format each user model with only the necessary columns.
        $sq = $request->query('search');
        $data = User::when(!isset($sq), fn($q) => (
                    $q->where('id', '!=', auth()->id())
                        ->whereDoesntHave('followers', fn($q) => $q->where('id', auth()->id()))
                ))
                ->when(isset($sq), fn($q) => $q->searchUser($sq))
                ->withPaginated(20, $this->basic_columns);

        return response()->json($data);
    }

    /**
     * Get suggested user models.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSuggested()
    {
        $data = User::where('id', '!=', auth()->id())
                    ->inRandomOrder()
                    ->limit(3)
                    ->get($this->basic_columns);

        return response()->json(compact('data'));
    }

    /**
     * Get user models with name or username containing a specific pattern.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function search(Request $request)
    {
        $query = $request->query('query');

        if (!isset($query) || is_null($query) || empty($query)) {
            throw ValidationException::withMessages([
                'query' => 'Please provide a pattern for the search query.'
            ]);
        }

        $data = DB::table('users')
                    ->searchUser($query)
                    ->limit(5)
                    ->get($this->basic_columns);

        return response()->json(compact('data'));
    }

    /**
     * Add a user model to the list of followed users.
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
     * Remove a user model from the list of followed users.
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
