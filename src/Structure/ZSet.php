<?php

namespace App\Service\Redis\Structure;

class ZSet extends Base {

    const ASC = 0;
    const DESC = 1;

    public function __construct($name, $ttl) {
        parent::__construct("ZSet:{$name}", $ttl);
    }

    /**
     * Adds the specified item with a given score to the sorted set.
     *
     * @param string $item
     * @param float  $score
     *
     * @return int Number of items added
     */
    public function add($item, $score) {
        $ret = $this->client()->zAdd($this->name(), [$item => $score]);
        if ($ret) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Adds the mutiple item with given score to the sorted set.
     *
     * @param array $arr
     *                   [$name1 => $score1, $name2 => $score2 ... ]
     *
     * @return int Number of items added
     */
    public function mAdd(array $arr) {
        $ret = $this->client()->zAdd($this->name(), $arr);
        if ($ret) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Returns the cardinality of the ordered set.
     *
     * @return int the set's cardinality
     */
    public function count() {
        return $this->card();
    }

    /**
     * Returns the cardinality of the ordered set.
     *
     * @return int the set's cardinality
     */
    private function card() {
        return $this->client(self::SLAVE)->zCard($this->name());
    }

    /**
     * Returns the number of elements of the sorted set which have scores in the range [min,max].
     * Adding a parenthesis before start or end excludes it from the range. +inf and -inf are also valid limits.
     *
     * @param string $min
     * @param string $max
     *
     * @return int number of elements which have scores in the range [min,max]
     */
    public function zCount($min, $max) {
        return $this->client(self::SLAVE)->zCount($this->name(), $min, $max);
    }

    /**
     * Increments the score of a item from the sorted set by a given amount and refresh the sorted set.
     *
     * @param string $item
     * @param float  $value (double) value that will be added to the item's score
     *
     * @return float the new value
     */
    public function incr($item, $incr = 1) {
        $ret = $this->client()->zIncrBy($this->name(), $incr, $item);
        if ($ret == $incr) {
            $this->refresh();
        }

        return $ret;
    }

    /**
     * Returns a range of elements from the ordered set stored at the specified key,
     * with values in the range [start, end]. start and stop are interpreted as zero-based indices:
     * 0 the first element,
     * 1 the second ...
     * -1 the last element,
     * -2 the penultimate ...
     *
     * @param int $start
     * @param int $end
     * @param int $sort
     *
     * @return array Array containing the values in specified range with their scores, using the specified sorting method.
     */
    public function range($start, $end, $sort = self::ASC) {
        $options['withscores'] = true;
        if ($sort == self::ASC) {
            return $this->client(self::SLAVE)->zRange($this->name(), $start, $end, $options);
        } else {
            return $this->client(self::SLAVE)->zRevRange($this->name(), $start, $end, $options);
        }
    }

    /**
     * Returns the elements of the sorted set which have scores in the range [min, max].
     * Adding a parenthesis before start or end excludes it from the range.
     * +inf and -inf are also valid limits.
     * Count and Offset are used if you don't want the full list of results (i.e. you want to paginate results).
     * Count is the number of results you want to display
     * Offset is the number of results you already displayed.
     * For example,
     * if you want the first 10 results, count = 10, offset = 0
     * if you want the results 41 to 50, count = 10, offset = 40.
     *
     * @param      $min
     * @param      $max
     * @param int  $sort
     * @param null $count
     * @param int  $offset
     * @param bool $withscores
     *
     * @return mixed
     */
    public function rangeByScore($min, $max, $sort = self::ASC, $count = null, $offset = 0, $withscores = true) {
        if ($count !== null) {
            $options['limit'] = array($offset, $count);
        }
        $options['withscores'] = $withscores;

        if ($sort == self::ASC) {
            return $this->client(self::SLAVE)->zRangeByScore($this->name(), $min, $max, $options);
        } else {
            return $this->client(self::SLAVE)->zRevRangeByScore($this->name(), $max, $min, $options);
        }
    }

    /**
     * Returns the rank of a given item in the sorted set.
     * if sorting method is self::ASC, rank starting at 0 for the item with the smallest score.
     * if sorting method is self::DESC, rank starting at 0 for the item with the largest score.
     *
     * @param string $item
     * @param bool   $sort (self::ASC or self::DESC)
     *
     * @return int the item's score.
     */
    public function rank($item, $sort = self::ASC) {
        $func = $sort == self::ASC ? 'zRank' : 'zRevRank';

        return $this->client(self::SLAVE)->$func($this->name(), $item);
    }

    /**
     * Returns the score of a given item in the sorted set.
     *
     * @param string $item
     *
     * @return float
     */
    public function score($item) {
        return $this->client(self::SLAVE)->zScore($this->name(), $item);
    }

    /**
     * Deletes a specified item from the ordered set.
     *
     * @param string $item
     *
     * @return int Number of deleted values
     */
    public function remove($item) {
        return $this->client()->zRem($this->name(), $item);
    }

    /**
     * Deletes the elements of the sorted set which have rank in the range [start,end].
     *
     * @param int $start (0-based)
     * @param int $end   (0-based)
     *
     * @return int The number of values deleted from the sorted set
     */
    public function removeByRank($start, $end) {
        return $this->client()->zRemRangeByRank($this->name(), $start, $end);
    }

    /**
     * Deletes the elements of the sorted set which have scores in the range [min, max].
     *
     * @param float|string $min double or "+inf" or "-inf" string
     * @param float|string $max double or "+inf" or "-inf" string
     *
     * @return int The number of values deleted from the sorted set
     */
    public function removeByScore($min, $max) {
        return $this->client()->zRemRangeByScore($this->name(), $min, $max);
    }

    public function union($destination, array $candidate, array $weight = [], $option = 'SUM') {
        return $this->client()->zunionstore('ZSet:' . $destination, array_map(function ($n) {
            return 'ZSet:' . $n;
        }, $candidate), ['WEIGHTS' => $weight, 'AGGREGATE' => $option]);
    }

    public function pop() {
        $ret = $this->client()->multi()
            ->zRange($this->name(), 0, 0, true)
            ->zRemRangeByRank($this->name(), 0, 0)
            ->exec();

        return $ret[0];
    }

    public function popByScore($max) {
        $ret = $this->client()->multi()
            ->zRangeByScore($this->name(), 0, $max)
            ->zRemRangeByScore($this->name(), 0, $max)
            ->exec();

        return $ret[0];
    }

    public function popByRank($start, $end, $withScore = true) {
        $ret = $this->client()->multi()
            ->zRange($this->name(), $start, $end, $withScore)
            ->zRemRangeByRank($this->name(), $start, $end)
            ->exec();

        return $ret[0];
    }

}
