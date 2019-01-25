<?php

namespace App\Service\Redis\Structure;

class Set extends Base {

    public function __construct($name, $ttl) {
        parent::__construct("Set:{$name}", $ttl);
    }

    /**
     * Adds a value to the set and refresh the set.
     * If this value is already in the set, FALSE is returned.
     *
     * @param string|array $value
     *
     * @return int The number of elements added to the set
     */
    public function add($value) {
        if (is_scalar($value)) {
            $value = [$value];
        }
        $ret = $this->client()->sAdd($this->name(), $value);
        if ($ret) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Adds a value to the set and refresh the set.
     * If this value is already in the set, FALSE is returned.
     *
     * @param string|array $value
     *
     * @return int The number of elements added to the set
     */
    public function addWithoutRefresh($value) {
        if (is_scalar($value)) {
            $value = [$value];
        }
        $ret = $this->client()->sAdd($this->name(), $value);
        if ($ret && $this->ttl() == -1 && $this->ttl >= 0) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Performs the difference between current set and another set and returns it.
     *
     * @param Set $otherRedisSet
     *
     * @return array of strings: current set - other set(“-”为集合减法）
     */
    public function diff($otherRedisSet) {
        return $this->client(self::SLAVE)->sDiff([$this->name(), $otherRedisSet->name()]);
    }

    /**
     * Performs the difference between current set and another set, stores it in a new set and returns it with ttl.
     *
     * @param Set $otherRedisSet
     * @param string  $newKey      name of the new set
     *
     * @return Set The new set
     */
    public function diffStore($otherRedisSet, $newKey) {
        $newSet = new static($newKey, $this->ttl);
        if ($this->client()->sDiffStore($newSet->name(), [$this->name(), $otherRedisSet->name()])) {
            $newSet->refresh();
        }

        return $newSet;
    }

    /**
     * Returns the members of a set resulting from the intersection of current set and another set and returns it.
     * If otherRedisSet is missing, FALSE is returned.
     *
     * @param Set $otherRedisSet
     *
     * @return array, contain the result of the intersection between the two sets
     *                If the intersection between the different sets is empty, the return value will be empty array.
     */
    public function inter($otherRedisSet) {
        return $this->client(self::SLAVE)->sInter([$this->name(), $otherRedisSet->name()]);
    }

    /**
     * Returns the members of a set resulting from the intersection of current set and another set,
     * stores it in a new set and returns it with ttl.
     * If otherRedisSet is missing, FALSE is returned.
     *
     * @param Set $otherRedisSet
     * @param string  $newKey      name of the new set
     *
     * @return Set The new set
     */
    public function interStore($otherRedisSet, $newKey) {
        $newSet = new static($newKey, $this->ttl);
        if ($this->client()->sInterStore($newSet->name(), [$this->name(), $otherRedisSet->name()])) {
            $newSet->refresh();
        }

        return $newSet;
    }

    /**
     * Checks if value is a member of the set.
     *
     * @param string $value
     *
     * @return bool TRUE if value is a member of the set, FALSE otherwise.
     */
    public function isMember($value) {
        return $this->client(self::SLAVE)->sIsMember($this->name(), $value);
    }

    /**
     * Returns the contents of the set.
     *
     * @return array An array of elements, the contents of the set.
     */
    public function members() {
        return $this->client(self::SLAVE)->sMembers($this->name());
    }

    /**
     * Moves the specified member from the set to another set.
     *
     * @param Set $otherRedisSet
     * @param string     $member
     *
     * @return bool If the operation is successful, return TRUE.
     *              If source set or otherRedisSet didn't exist, or the member didn't exist, FALSE is returned.
     */
    public function moveTo($otherRedisSet, $member) {
        return $this->client()->sMove($this->name(), $otherRedisSet->name(), $member);
    }

    /**
     * Removes and returns a random element from the set value at Key.
     *
     * @param string $key
     *
     * @return string "popped" value
     *                bool FALSE if set identified by key is empty or doesn't exist.
     */
    public function pop() {
        return $this->client()->sPop($this->name());
    }

    /**
     * Returns a random element from the set, without removing it.
     *
     * @return string value from the set
     *                bool FALSE if set is empty or doesn't exist.
     */
    public function randMember() {
        return $this->client(self::SLAVE)->sRandMember($this->name());
    }

    /**
     * Removes the specified member from the set.
     *
     * @param string $member
     *
     * @return int The number of elements removed from the set.
     */
    public function remove($member) {
        return $this->client()->sRem($this->name(), $member);
    }

    /**
     * Returns the cardinality of the set.
     *
     * @return int the cardinality of the set identified by key, 0 if the set doesn't exist.
     */
    public function count() {
        return $this->client(self::SLAVE)->sCard($this->name());
    }

    /**
     * Returns the members of a set resulting from the union of current set and another set and returns it.
     * If otherRedisSet is missing, FALSE is returned.
     *
     * @param Set $otherRedisSet
     *
     * @return array, contain the result of the union of the two sets
     */
    public function union($otherRedisSet) {
        return $this->client(self::SLAVE)->sUnion([$this->name(), $otherRedisSet->name()]);
    }

    /**
     * Returns the members of a set resulting from the union of current set and another set,
     * stores it in a new set and returns it with ttl.
     *
     * @param Set $otherRedisSet
     * @param string  $newKey      name of the new set
     *
     * @return Set The new set
     */
    public function unionStore($otherRedisSet, $newKey) {
        $newSet = new static($newKey, $this->ttl);
        if ($this->client()->sunionstore($newSet->name(), [$this->name(), $otherRedisSet->name()])) {
            $newSet->refresh();
        }

        return $newSet;
    }
}
