<?php

namespace App\Providers;

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
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('owners')) {
                $owner = \App\Models\Owner::first();
                if ($owner && $owner->name) {
                    config(['mail.from.name' => $owner->name]);
                }
            }
        } catch (\Exception $e) {
            // Do nothing if database connection fails during initial boot/migrations
        }
    }
}
