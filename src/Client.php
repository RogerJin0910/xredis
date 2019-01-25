<?php

namespace App\Service\Redis;

use Predis\Client as PredisClient;

class Client {

    static $client = null;

    /**
     * @param $parameters
     * @param $options
     * @return null|PredisClient
     */
    public static function instance($parameters, $options) {
        if (is_null(self::$client)) {
            self::$client = new PredisClient($parameters, $options);
        }

        return self::$client;
    }
}