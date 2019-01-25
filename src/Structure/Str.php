<?php

namespace App\Service\Redis\Structure;

class Str extends Base {

    public function __construct($name, $ttl) {
        parent::__construct("Str:{$name}", $ttl);
    }

    /**
     * Returns the values of all specified keys.
     *
     * For every key that does not hold a string value or does not exist,
     * the special value false is returned. Because of this, the operation never fails.
     *
     * @param array $keys
     *
     * @return array
     */
    public function mget(array $keys) {
        return $this->client(self::SLAVE)->mget($keys);
    }

    /**
     * Sets multiple key-value pairs in one atomic command, with ttl.
     *
     * @param array(key => value) $keyValArray : array(key => value, ...)
     *
     * @return bool TRUE in case of success, FALSE in case of failure.
     *              现在这东西已经有点坑爹了，还好只是我在用。。传进来的array需要以Str:开头（string格式，他不会自己拼的）。
     */
    public function mset(array $keyValArray) {
        $ret = $this->client()->mset($keyValArray);
        foreach ($keyValArray as $k => $v) {
            $this->client()->expire($k, $this->ttl);
        }

        return $ret;
    }

    /**
     * Sets multiple key-value pairs in one atomic command, with ttl.
     * Only returns TRUE if all the keys were set (see SETNX).
     *
     * @param array(key => value) $keyValArray : array(key => value, ...)
     *
     * @return bool
     *              现在这东西没人用，特殊说明同上面的mset
     */
    public function msetnx(array $keyValArray) {
        $ret = $this->client()->msetnx($keyValArray);
        foreach ($keyValArray as $k => $v) {
            $this->client()->expire($k, $this->ttl);
        }

        return $ret;
    }

    /**
     * Append specified string to current string.
     *
     * @param string $value
     *
     * @return int: Size of the value after the append
     */
    public function append($value) {
        return $this->client()->append($this->name(), $value);
    }

    /**
     * Decrement the number stored in the string by $decrement.
     *
     * @param int $value that will be substracted
     *
     * @return int the new value
     */
    public function decr($decrement = 1) {
        return $this->client()->decrBy($this->name(), $decrement);
    }

    /**
     * Increment the number stored in the string by $increment, with ttl.
     *
     * @param int $increment
     *
     * @return int the new value
     */
    public function incr($increment = 1) {
        $client = $this->client();
        $ret = $client->incrBy($this->name(), $increment);
        if ($ret == $increment) $this->refresh();
        return $ret;
    }

    /**
     * Get the value of the string.
     *
     * @return string|bool: If string didn't exist, FALSE is returned. Otherwise, the value is returned.
     */
    public function get() {
        return $this->client(self::SLAVE)->get($this->name());
    }

    /**
     * Sets a value with ttl and returns the previous entry of the string.
     *
     * @param string $value
     *
     * @return string A string, the previous value of the string.
     */
    public function getSet($value) {
        $oldValue = $this->client()->getSet($this->name(), $value);
        $this->refresh();

        return $oldValue;
    }

    /**
     * Set the string value as $value, with ttl.
     *
     * @param string $value
     *
     * @return bool: TRUE if the command is successful.
     */
    public function set($value) {
        if ($this->ttl > 0) {
            return $this->setex($value);
        } else {
            return $this->client()->set($this->name(), $value);
        }
    }

    /**
     * Set the string value as $value, and refresh it.
     *
     * @param string $value
     *
     * @return bool: TRUE if the command is successful.
     */
    public function setex($value) {
        return $this->client()->setex($this->name(), $this->ttl, $value);
    }

    /**
     * If the string doesn't already exist in the database, set the string value as $value, with ttl.
     *
     * @param string $value
     *
     * @return bool: TRUE in case of success, FALSE in case of failure.
     */
    public function setnx($value) {
        return $this->client()->setnx($this->name(), $value) && $this->refresh();
    }

    /**
     * Get the length of the string.
     *
     * @return int
     */
    public function strlen() {
        return $this->client(self::SLAVE)->strlen($this->name());
    }
}
