<?php

namespace App\Service\Redis;

use App\Service\Redis\Structure\Counter;
use App\Service\Redis\Structure\Hash;
use App\Service\Redis\Structure\RList;
use App\Service\Redis\Structure\Set;
use App\Service\Redis\Structure\Str;
use App\Service\Redis\Structure\ZSet;

class Factory {

    private static $structures = [
        'string' => Str::class,
        'counter' => Counter::class,
        'hash' => Hash::class,
        'set' => Set::class,
        'rlist' => RList::class,
        'zset' => ZSet::class,
    ];

    private static $objectPool = [];

    public function __call($method, $args) {
        $structureClass = self::getStructureClass($method);
        if (!$structureClass) {
            return false;
        }
        array_push($args, $method);
        array_push($args, $structureClass);

        return call_user_func_array([$this, 'redis'], $args);
    }

    private function redis($name, $ttl, $structure, $class) {
        $poolKey = strtolower($structure) . '_' . $name;

        if ($redis = self::poolGet($poolKey)) {
            return $redis;
        }

        /** @var Structure\Base $redis */
        $redis = new $class($name, $ttl);

        return self::poolSet($poolKey, $redis);
    }

    private function getStructureClass($func) {
        return self::$structures[$func] ?? null;
    }

    private static function poolGet($key) {
        return isset(self::$objectPool[$key]) ? self::$objectPool[$key] : null;
    }

    private static function poolSet($key, $object) {
        self::$objectPool[$key] = $object;

        return $object;
    }

    /**
     * @param $name
     * @param $ttl
     *
     * @return Structure\Str
     */
    public function string($name, $ttl) {
        return $this->redis($name, $ttl, __FUNCTION__, $this->getStructureClass(__FUNCTION__));
    }

    /**
     * @param $name
     * @param $ttl
     *
     * @return Structure\Counter
     */
    public function counter($name, $ttl) {
        return $this->redis($name, $ttl, __FUNCTION__, $this->getStructureClass(__FUNCTION__));
    }

    /**
     * @param $name
     * @param $ttl
     *
     * @return Structure\Hash
     */
    public function hash($name, $ttl) {
        return $this->redis($name, $ttl, __FUNCTION__, $this->getStructureClass(__FUNCTION__));
    }

    /**
     * @param $name
     * @param $ttl
     *
     * @return Structure\Set
     */
    public function set($name, $ttl) {
        return $this->redis($name, $ttl, __FUNCTION__, $this->getStructureClass(__FUNCTION__));
    }

    /**
     * @param $name
     * @param $ttl
     *
     * @return Structure\ZSet
     */
    public function zset($name, $ttl) {
        return $this->redis($name, $ttl, __FUNCTION__, $this->getStructureClass(__FUNCTION__));
    }

    /**
     * @param $name
     * @param $ttl
     *
     * @return Structure\RList
     */
    public function rlist($name, $ttl) {
        return $this->redis($name, $ttl, __FUNCTION__, $this->getStructureClass(__FUNCTION__));
    }
}
