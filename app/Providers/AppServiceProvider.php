<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Mixins\PaginationMixin;

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
        Builder::mixin(new PaginationMixin);
        Relation::mixin(new PaginationMixin);

        QueryBuilder::macro('searchUser', function(string $query) {
            return $this->where('name', 'ilike', "%$query%")->orWhere('username', 'like', "%$query%");
        });
    }
}
