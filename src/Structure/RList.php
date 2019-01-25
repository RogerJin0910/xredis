<?php

namespace App\Service\Redis\Structure;

class RList extends Base {

    public function __construct($name, $ttl) {
        parent::__construct("List:{$name}", $ttl);
    }

    /**
     * Return the specified element of the list.
     * 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     * Return FALSE in case of a bad index.
     *
     * @param int $index
     *
     * @return string the element at this index
     *                Bool FALSE if no value corresponds to this index in the list.
     */
    public function getById($index) {
        return $this->client(self::SLAVE)->lIndex($this->name(), $index);
    }

    /**
     * Insert value in the list before or after the pivot value. the parameter options
     * specify the position of the insert (before or after). If the list didn't exists,
     * or the pivot didn't exists, the value is not inserted.
     *
     * @param string $pivot
     * @param int    $position Factory::BEFORE | Factory::AFTER
     * @param string $value
     *
     * @return int The number of the elements in the list, -1 if the pivot didn't exists.
     *
     * @example
     *    $a = new \Factory\RList("sampleRList", 0.01); $a->delete();
     *    $a->lPush('A');                    // var_dump($a->getAll()): array('A');
     *    $a->insert('B', 'after', 'A');        // var_dump($a->getAll()): array('A', 'B');
     *    $a->insert('B', 'before', 'B');    // var_dump($a->getAll()): array('A', 'B', 'B');
     *    $a->insert('c', 'after', 'B');        // var_dump($a->getAll()): array('A', 'B', 'c', 'B');
     */
    public function insert($value, $pos, $pivot) {
        switch ($pos) {
            case 'before':
                $pos = \Redis::BEFORE;
                break;
            case 'after':
                $pos = \Redis::AFTER;
                break;
            default:
                throw new \Exception('Argument pos invalid!');
        }

        return $this->client()->lInsert($this->name(), $pos, $pivot, $value);
    }

    /**
     * Returns the size of the list. If the list didn't exist or is empty, the command returns 0.
     *
     * @return int The size of the list.
     */
    public function count() {
        return $this->client(self::SLAVE)->lLen($this->name());
    }

    /**
     * Returns and removes the first element of the list.
     *
     * @return string if command executed successfully BOOL FALSE in case of failure (empty list)
     */
    public function lPop() {
        return $this->client()->lPop($this->name());
    }

    /**
     * Adds the string values to the head (left) of the list and refresh the list.
     * Creates the list if the list didn't exist.
     *
     * @param string $value String, value to push in list
     *
     * @return int The new length of the list in case of success, FALSE in case of Failure.
     */
    public function lPush($value) {
        $ret = $this->client()->lPush($this->name(), $value);
        if ($ret) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Adds the string value to the head (left) of the list if the list exists.
     *
     * @param string $value String, value to push in key
     *
     * @return int The new length of the list in case of success, FALSE in case of Failure.
     */
    public function lPushx($value) {
        return $this->client()->lPushx($this->name(), $value);
    }

    /**
     * Returns the specified elements of the list in the range [start, end].
     * start and stop are interpretated as indices: 0 the first element,
     * 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @param int $start
     * @param int $end
     *
     * @return array containing the values in specified range.
     */
    public function getByRange($start, $end) {
        return $this->client(self::SLAVE)->lRange($this->name(), $start, $end);
    }

    /**
     * Returns all elements of the list.
     *
     * @return array containing all values.
     */
    public function getAll() {
        return $this->client(self::SLAVE)->lRange($this->name(), 0, -1);
    }

    /**
     * Removes all occurences of the value element from the list.
     *
     * @param string $value
     *
     * @return int the number of elements to remove
     */
    public function removeAll($value) {
        return $this->client()->lRem($this->name(), $value, 0);
    }

    /**
     * Removes the first count occurences of the value element from the list head(left).
     *
     * @param string $value
     * @param int    $count
     *
     * @return int the number of elements to remove
     */
    public function removeFromLeft($value, $count) {
        return $this->client()->lRem($this->name(), $value, $count);
    }

    /**
     * Removes the first count occurences of the value element from the list tail(right).
     *
     * @param string $value
     * @param int    $count
     *
     * @return int the number of elements to remove
     */
    public function removeFromRight($value, $count) {
        return $this->client()->lRem($this->name(), $value, -$count);
    }

    /**
     * Set the list at index with the new value.
     *
     * @param int    $index (0-based)
     * @param string $value
     *
     * @return bool TRUE if the new value is setted. FALSE if the index is out of range.
     */
    public function setByIndex($index, $value) {
        return $this->client()->lSet($this->name(), $index, $value);
    }

    /**
     * Trims an existing list so that it will contain only a specified range of elements.
     *
     * @param int $start (0-based)
     * @param int $stop  (0-based)
     *
     * @return array Bool return FALSE if the list do not exist.
     */
    public function trim($start, $end) {
        return $this->client()->lTrim($this->name(), $start, $end);
    }

    /**
     * Returns and removes the last element of the list.
     *
     * @return string if command executed successfully BOOL FALSE in case of failure (empty list)
     */
    public function rPop() {
        return $this->client()->rPop($this->name());
    }

    /**
     * Pops a value from the tail of current list, and pushes it to the front of another list.
     * Also return this value.
     *
     * @since   redis >= 1.1
     *
     * @param RList $otherRedisList
     *
     * @return string The element that was moved in case of success, FALSE in case of failure.
     */
    public function rPopLPushTo($otherRedisList) {
        return $this->client()->rpoplpush($this->name(), $otherRedisList->name());
    }

    /**
     * Adds the string value to the tail (right) of the list and refresh the list.
     * Creates the list if the list didn't exist.
     *
     * @param string $value
     *
     * @return int The new length of the list in case of success, FALSE in case of Failure.
     */
    public function rPush($value) {
        $ret = $this->client()->rPush($this->name(), $value);
        if ($ret) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Adds the string value to the tail (right) of the list if the list exists. FALSE in case of Failure.
     *
     * @param string $value
     *
     * @return int The new length of the list in case of success, FALSE in case of Failure.
     */
    public function rPushx($value) {
        return $this->client()->rPushx($this->name(), $value);
    }
}
