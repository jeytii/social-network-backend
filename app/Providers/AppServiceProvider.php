<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rules\Password;
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
        Password::defaults(fn() => (
            Password::min(config('validation.min_lengths.password'))
                ->mixedCase()
                ->numbers()
                ->symbols()
        ));

        Builder::mixin(new PaginationMixin);
        Relation::mixin(new PaginationMixin);

        QueryBuilder::macro('searchUser', function(string $query) {
            return $this->where('name', 'ilike', "%$query%")->orWhere('username', 'like', "%$query%");
        });
        
        Request::macro('isPresent', fn(string $key) => $this->has($key) && $this->filled($key));
    }
}
