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
        $data = User::when(!$hasQuery, fn($q) => (
                    $q->where('id', '!=', auth()->id())
                        ->whereDoesntHave('followers', fn($q) => $q->where('id', auth()->id()))
                ))
                ->when($hasQuery, fn($q) => $q->searchUser($request->query('query')))
                ->withPaginated(20, config('api.response.user.basic'));

        return array_merge($data, [
            'status' => 200,
            'message' => 'Successfully retrieved data.',
        ]);
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
        $message = 'Successfully retrieved data.';
        $status = 200;

        return compact('status', 'message', 'data');
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
        $status = 200;
        $message = 'Successfully retrieved data.';

        if (!$request->has('query') || $request->isNotFilled('query')) {
            return [
                'status' => $status,
                'message' => $message,
                'data' => [],
            ];
        }

        $data = DB::table('users')
                    ->searchUser($request->query('query'))
                    ->limit($count)
                    ->get(config('api.response.user.basic'));

        return compact('status', 'message', 'data');
    }
}