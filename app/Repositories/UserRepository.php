<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    /**
     * Get 20 users on each offset.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function get(Request $request): array
    {
        $id = auth()->id();

        $data = User::when(!$request->isPresent('query'), fn($query) => (
                    $query->whereKeyNot($id)->whereDoesntHave('followers', fn($q) => $q->whereKey($id))
                ))
                ->when($request->isPresent('query'), fn($q) => $q->searchUser($request->query('query')))
                ->when($request->isPresent('month'), fn($q) => $q->whereMonth('birth_date', $request->query('month')))
                ->when($request->isPresent('year'), fn($q) => $q->whereYear('birth_date', $request->query('year')))
                ->when($request->isPresent('gender'), fn($q) => $q->where('gender', $request->query('gender')))
                ->withPaginated(20, config('api.response.user.basic'));

        return array_merge($data, [
            'status' => 200,
        ]);
    }

    /**
     * Get the currently logged-in user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function getAuthUser(Request $request): array
    {
        $user = $request->user()->only(array_merge(
            config('api.response.user.basic'),
            ['email', 'bio', 'color', 'dark_mode']
        ));

        $user['birth_date'] = $request->user()->birth_date->format('Y-m-d');

        return [
            'status' => 200,
            'data' => $user,
        ];
    }

    /**
     * Get 3 random users.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param int  $count
     * @return array
     */
    public function getRandom(Request $request, int $count = 3): array
    {
        $data = cache()->remember('user-suggestions', 60, function() use ($request, $count) {
            $exceptIds = $request->user()->following()->pluck('id')->toArray();

            return User::whereNotIn('id', [auth()->id(), ...$exceptIds])
                        ->inRandomOrder()
                        ->limit($count)
                        ->get(config('api.response.user.basic'));
        });

        return [
            'status' => 200,
            'data' => $data,
        ];
    }

    /**
     * Get search results according to the provided query.
     * 
     * @param \Illuminate\Http\Request  $string
     * @param int  $count
     * @return array
     */
    public function search(Request $request, int $count = 5): array
    {
        $data = [];

        if ($request->isPresent('query')) {
            $data = DB::table('users')
                        ->searchUser($request->query('query'))
                        ->limit($count)
                        ->get(config('api.response.user.basic'));
        }

        return [
            'status' => 200,
            'data' => $data,
        ];
    }
}
