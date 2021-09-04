<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
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

        Builder::macro('withFormattedPosts', fn() => (
            $this->with('user:id,slug,name,username,gender,image_url')
                ->withCount(['likers as likes_count', 'comments'])
        ));

        QueryBuilder::macro('searchUser', fn(string $query) => (
            $this->where('name', 'ilike', "%$query%")
                ->orWhere('username', 'like', "%$query%")
        ));

        Relation::macro('withFormattedPosts', fn() => (
            $this->with('user:id,slug,name,username,gender,image_url')
                ->withCount(['likers as likes_count', 'comments'])
        ));
    }
}
