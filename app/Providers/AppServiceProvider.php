<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\{
    Relation,
    HasMany,
    BelongsToMany
};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Repositories\Contracts\AuthRepositoryInterface', 'App\Repositories\AuthRepository');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('searchUser', fn(string $query) => (
            $this->where('name', 'ilike', "%$query%")
                ->orWhere('username', 'like', "%$query%")
        ));

        Builder::macro('withUser', fn() => (
            $this->with('user:id,slug,name,username,gender,image_url')
        ));

        Builder::macro('withPaginated', function(int $perPage = 20, array $columns = ['*']) {
            $data = $this->paginate($perPage, $columns);

            $hasMore = $data->hasMorePages();
            $nextOffset = $hasMore ? $data->currentPage() + 1 : null;

            return [
                'data' => $data->items(),
                'has_more' => $hasMore,
                'next_offset' => $nextOffset,
            ];
        });

        HasMany::macro('withPaginated', function(int $perPage = 20, array $columns = ['*']) {
            $data = $this->paginate($perPage, $columns);

            $hasMore = $data->hasMorePages();
            $nextOffset = $hasMore ? $data->currentPage() + 1 : null;

            return [
                'data' => $data->items(),
                'has_more' => $hasMore,
                'next_offset' => $nextOffset,
            ];
        });

        BelongsToMany::macro('withPaginated', function(int $perPage = 20, array $columns = ['*']) {
            $data = $this->paginate($perPage, $columns);

            $hasMore = $data->hasMorePages();
            $nextOffset = $hasMore ? $data->currentPage() + 1 : null;

            return [
                'data' => $data->items(),
                'has_more' => $hasMore,
                'next_offset' => $nextOffset,
            ];
        });

        QueryBuilder::macro('searchUser', fn(string $query) => (
            $this->where('name', 'ilike', "%$query%")
                ->orWhere('username', 'like', "%$query%")
        ));

        Relation::macro('withUser', fn() => (
            $this->with('user:id,slug,name,username,gender,image_url')
        ));
    }
}
