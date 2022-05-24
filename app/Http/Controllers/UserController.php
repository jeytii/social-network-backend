<?php

namespace App\Http\Controllers;

use App\Models\{User, Notification};
use App\Notifications\NotifyUponAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Get paginated list of user models.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $id = auth()->id();

        $data = User::when(!$request->isPresent('query'), fn($query) => (
                $query->whereKeyNot($id)->whereDoesntHave('followers', fn($q) => $q->whereKey($id))
            ))
            ->when($request->isPresent('query'), fn($q) => $q->searchUser($request->query('query')))
            ->when($request->isPresent('month'), fn($q) => $q->whereMonth('birth_date', $request->query('month')))
            ->when($request->isPresent('year'), fn($q) => $q->whereYear('birth_date', $request->query('year')))
            ->when($request->isPresent('gender'), fn($q) => $q->where('gender', $request->query('gender')))
            ->orderBy('name')
            ->withPaginated(20, config('response.user'));

        return response()->json($data);
    }

    /**
     * Get 3 randomly suggested users that the user is not yet following.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRandom(Request $request)
    {
        $data = cache()->remember('user-suggestions', 60, function() use ($request) {
            $exceptIds = $request->user()->following()->pluck('id')->toArray();

            return User::whereNotIn('id', [auth()->id(), ...$exceptIds])
                ->inRandomOrder()
                ->limit(3)
                ->get(config('response.user'));
        });

        return response()->json(compact('data'));
    }

    /**
     * Search user(s) by name or username.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $data = [];

        if ($request->isPresent('query')) {
            $data = DB::table('users')
                ->searchUser($request->query('query'))
                ->limit(5)
                ->get(config('response.user'));
        }

        return response()->json(compact('data'));
    }

    /**
     * Follow a user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function follow(Request $request, User $user)
    {
        $this->authorize('follow', $user);

        $follower = $request->user();

        DB::transaction(function() use ($follower, $user) {
            $follower->following()->sync([$user->id]);
            $user->notify(new NotifyUponAction($follower, Notification::FOLLOWED, "/{$follower->username}"));
        });

        return response()->success();
    }

    /**
     * Unfollow a user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unfollow(Request $request, User $user)
    {
        $this->authorize('unfollow', $user);

        $request->user()->following()->detach($user);

        return response()->success();
    }
}
