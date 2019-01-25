<?php

namespace App\Service\Redis;

use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider {
    public function register() {
        $this->app->singleton('XRedis', Factory::class);
    }
}
