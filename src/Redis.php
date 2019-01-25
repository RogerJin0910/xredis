<?php

namespace App\Service\Redis;

use Illuminate\Support\Facades\Facade;

class Redis extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'XRedis';
    }
}
