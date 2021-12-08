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
        $hasQuery = $request->has('query') || $request->filled('query');
        $hasMonth = $request->has('month') || $request->filled('month');
        $hasYear = $request->has('year') || $request->filled('year');
        $hasGender = $request->has('gender') || $request->filled('gender');

        $data = User::when(!$hasQuery, fn($q) => (
                    $q->where('id', '!=', auth()->id())
                        ->whereDoesntHave('followers', fn($q) => $q->where('id', auth()->id()))
                ))
                ->when($hasQuery, fn($q) => $q->searchUser($request->query('query')))
                ->when($hasMonth, fn($q) => $q->whereMonth('birth_date', $request->query('month')))
                ->when($hasYear, fn($q) => $q->whereYear('birth_date', $request->query('year')))
                ->when($hasGender, fn($q) => $q->where('gender', $request->query('gender')))
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
        $user = $request->user()->only(array_merge(config('api.response.user.basic'), ['email', 'phone_number', 'bio']));
        $birthdate = $request->user()->birth_date->format('Y-m-d');

        return [
            'status' => 200,
            'data' => array_merge($user, [
                'birth_date' => $birthdate,
            ]),
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
        $exceptIds = $request->user()->following()->pluck('id')->toArray();
        $data = User::whereNotIn('id', [auth()->id(), ...$exceptIds])
                    ->inRandomOrder()
                    ->limit($count)
                    ->get(config('api.response.user.basic'));

        return [
            'status' => 200,
            'data' => $data,
        ];
    }

    /**
     * Get the column values that will be used as route parameters on the client.
     * 
     * @param string  $column
     * @return array
     */
    public function getParams(string $column): array
    {
        $data = User::pluck($column);

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
        if (!$request->has('query') || $request->isNotFilled('query')) {
            return [
                'status' => 200,
                'data' => [],
            ];
        }

        $data = DB::table('users')
                    ->searchUser($request->query('query'))
                    ->limit($count)
                    ->get(config('api.response.user.basic'));

        return [
            'status' => 200,
            'data' => $data,
        ];
    }
}
