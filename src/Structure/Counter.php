<?php

namespace App\Service\Redis\Structure;

/**
 * Counter 类，只完成最简单的Counter工作.
 *
 * 【注意】：Counter和Firewall最大的区别是Counter每次incr()等写操作都会重置TTL，导致实际过期时间延长。
 */
class Counter extends Str {

    public function __construct($name, $ttl = 2592000 /* 30 days */) {
        parent::__construct('counter:' . $name, $ttl);
    }

    /**
     * Set一个Counter的时候会重置他的TTL		//这个复写其实可以不用了 by panjie.
     *
     * @param int $value
     *
     * @return bool: 如果成功返回TRUE
     */
    public function set($value) {
        return parent::setex($value);
    }

    /**
     * Increase
     * Notice: 会重置TTL.
     *
     * @param int $value
     *
     * @return int 递增之后Counter的值
     */
    public function incr($value = 1) {
        //这里不做incr和incrBy的特殊处理了，两者几乎没有区别
        $ret = parent::incr($value);
        $this->refresh();

        return $ret;
    }

    /**
     * Decrease
     * Notice: 会重置TTL.
     *
     * @param int $value
     *
     * @return int 递减之后Counter的值
     */
    public function decr($value = 1) {
        $ret = parent::decr($value);
        $this->refresh();

        return $ret;
    }
}
