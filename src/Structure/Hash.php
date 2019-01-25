<?php

namespace App\Service\Redis\Structure;

class Hash extends Base {

    public function __construct($name, $ttl) {
        parent::__construct("Hash:{$name}", $ttl);
    }

    /**
     * Removes a values from the hash stored at key.
     * If the key doesn't exist, FALSE is returned.
     *
     * @param string $hashKey
     *
     * @return int Number of deleted fields
     */
    public function del($hashKey) {
        return $this->client()->hDel($this->name(), [$hashKey]);
    }

    /**
     * Removes a values from the hash stored at key.
     * If the key doesn't exist, FALSE is returned.
     *
     * @param array $keyArray
     *
     * @return int Number of deleted fields
     */
    public function mdel(array $keyArray) {
        return $this->client()->hDel($this->name(), $keyArray);

    }

    /**
     * Verify if the specified member exists.
     *
     * @param string $hashKey
     *
     * @return bool If the member exists in the hash table, return TRUE, otherwise return FALSE.
     */
    public function contains($hashKey) {
        return $this->client(self::SLAVE)->hExists($this->name(), $hashKey);
    }

    /**
     * Gets a value from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param string $hashKey
     *
     * @return string The value, if the command executed successfully BOOL FALSE in case of failure
     */
    public function get($hashKey) {
        return $this->client(self::SLAVE)->hGet($this->name(), $hashKey);
    }

    /**
     * Returns the whole hash, as an array of strings indexed by strings.
     *
     * @return array An array of elements, the contents of the hash.
     */
    public function getAll() {
        return $this->client(self::SLAVE)->hGetAll($this->name());
    }

    /**
     * Increments the value of a member from a hash by a given amount and refresh the hash.
     *
     * @param string $hashKey
     * @param int    $value   (integer) value that will be added to the member's value
     *
     * @return int the new value
     */
    public function incrBy($hashKey, $value) {
        $ret = $this->client()->hIncrBy($this->name(), $hashKey, $value);
        if ($ret == $value) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Increment the float value of a hash field by the given amount and refresh the hash.
     *
     * @param string $field
     * @param float  $increment
     *
     * @return float
     */
    public function incrByFloat($field, $increment) {
        $ret = $this->client()->hIncrByFloat($this->name(), $field, $increment);
        if ($ret == $increment) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Returns the keys in hash, as an array of strings.
     *
     * @return array An array of elements, the keys of the hash. This works like PHP's array_keys().
     */
    public function keys() {
        return $this->client(self::SLAVE)->hKeys($this->name());
    }

    /**
     * Returns the length of hash, in number of items.
     *
     * @return int the number of items in the hash, FALSE if the hash doesn't exist.
     */
    public function count() {
        return $this->client(self::SLAVE)->hLen($this->name());
    }

    /**
     * Retirieve the values associated to the specified fields in the hash.
     *
     * @param array $hashKeys
     *
     * @return array Array An array of elements, the values of the specified fields in the hash,
     *               with the hash keys as array keys.
     */
    public function mget(array $keyValueArray) {
        //@todo 新版client加了类似判断，等我们client升级了后就把这块去掉
        foreach ($keyValueArray as $k => $v) {
            if (is_string($v) || is_int($v)) {
                continue;
            }
            unset($keyValueArray[$k]);
        }
        if (!$keyValueArray) {
            return [];
        }

        return $this->client(self::SLAVE)->hMGet($this->name(), $keyValueArray);
    }

    /**
     * Fills in a whole hash with ttl. Non-string values are converted to string, using the standard (string) cast.
     * NULL values are stored as empty strings.
     *
     * @param array $hashKeys key => value array
     *
     * @return bool
     */
    public function mset(array $keyValueArray) {
        return $this->client()->hMset($this->name(), $keyValueArray) && $this->refresh();
    }

    /**
     * Adds a value to the hash and refresh it.
     *
     * @param string $hashKey
     * @param string $value
     *
     * @return int|bool
     *                  1 if value didn't exist and was added successfully,
     *                  0 if the value was already present and was replaced, FALSE if there was an error.
     */
    public function set($hashKey, $value) {
        $ret = $this->client()->hSet($this->name(), $hashKey, $value);
        if ($ret !== false) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Adds a value to the hash only if this field isn't already in the hash and refresh the hash.
     *
     * @param string $hashKey
     * @param string $value
     *
     * @return bool TRUE if the field was set, FALSE if it was already present.
     */
    public function setNx($hashKey, $value) {
        return $this->client()->hSetNx($this->name(), $hashKey, $value) && $this->refresh();
    }

    /**
     * Returns the values in the hash, as an array of strings.
     *
     * @return array An array of elements, the values of the hash. This works like PHP's array_values().
     */
    public function vals() {
        return $this->client(self::SLAVE)->hVals($this->name());
    }
}
