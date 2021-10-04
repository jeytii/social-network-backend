<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    private array $columns;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->columns = array_merge(config('api.response.user.basic'), ['slug']);
    }

    /**
     * Get 20 users on each offset.
     * 
     * @param string|null  $query
     * @return array
     */
    public function get(?string $query): array
    {
        $hasQuery = isset($query);
        $data = User::when(!$hasQuery, fn($q) => (
                    $q->where('id', '!=', auth()->id())
                        ->whereDoesntHave('followers', fn($q) => $q->where('id', auth()->id()))
                ))
                ->when($hasQuery, fn($q) => $q->searchUser($query))
                ->withPaginated(20, $this->columns);

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
                    ->get($this->columns);
        $message = 'Successfully retrieved data.';
        $status = 200;

        return compact('status', 'message', 'data');
    }

    /**
     * Get search results according to the provided query.
     * 
     * @param string|null  $query
     * @param int  $count
     * @return array
     */
    public function search(?string $query, int $count = 5): array
    {
        $status = 200;
        $message = 'Successfully retrieved data.';

        if (!isset($query) || is_null($query) || empty($query)) {
            return [
                'status' => $status,
                'message' => $message,
                'data' => [],
            ];
        }

        $data = DB::table('users')
                    ->searchUser($query)
                    ->limit($count)
                    ->get($this->columns);

        return compact('status', 'message', 'data');
    }
}