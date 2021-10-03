<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Mixins\{UserMixin, PaginationMixin};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // TODO: Remove this
        $this->app->bind('App\Repositories\Contracts\AuthRepositoryInterface', 'App\Repositories\AuthRepository');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::mixin(new UserMixin);
        Builder::mixin(new PaginationMixin);

        Relation::mixin(new UserMixin);
        Relation::mixin(new PaginationMixin);

        QueryBuilder::mixin(new UserMixin);
    }
}
