<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::macro('withFormattedPosts', function() {
            return $this->with('user:id,slug,name,username,gender,image_url')
                ->withCount(['likers as likes_count', 'comments']);
        });
    }
}
