<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */

    public function boot()
    {
//        Relation::morphMap([
//            'ticket' => 'App\Ticket',
//            'user' => 'App\User'
//        ]);
        Schema::defaultStringLength(191);
    }
// Just cannot get it to work... changing the source_type column now...

    public function register()
    {
        //
    }

}
