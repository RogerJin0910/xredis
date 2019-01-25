<?php

namespace App\Service\Redis\Structure;

use App\Service\Redis\Client as PredisClientGenerator;
use Illuminate\Support\Facades\Config;

abstract class Base {

    const MASTER = 'master';
    const SLAVE = 'slave';

    protected $name;
    protected $ttl;

    /** @var \Predis\Client $client */
    protected $client;

    /**
     * @param string $name 自己保证不要和别人的重复哦~
     * @param int    $ttl  过期时间，单位为秒。
     */
    public function __construct($name, $ttl) {
        $this->name = 'XRedis:' . $name;
        $this->ttl = $ttl;
        $this->client = PredisClientGenerator::instance(
            Config::get('xredis.parameters'),
            Config::get('xredis.settings')
        );
    }

    /**
     * @return string key for redis
     */
    protected function name() {
        return $this->name;
    }

    /**
     * !!!这是删除整个集合，不是删除单条数据!!!
     *
     * @return int 删除对应的数据
     */
    public function delete() {
        return $this->client()->del([$this->name]);
    }

    /**
     * transaction 开始.
     *
     * @return bool
     */
    public function multi() {
        return $this->client()->multi();
    }

    /**
     * transaction 结束
     *
     * @return array
     */
    public function exec() {
        return $this->client()->exec();
    }

    /**
     * transaction 取消.
     *
     * @return bool
     */
    public function discard() {
        return $this->client()->discard();
    }

    /**
     * @param string $client
     * @return \Predis\Client
     */
    protected function client($client = self::MASTER) {
        return $this->client;
    }

    /**
     * @param string $clientMethod
     *
     * @return bool 返回数据是否存在
     */
    public function exists($clientMethod = self::SLAVE) {
        return $this->client($clientMethod)->exists($this->name);
    }

    /**
     * @return int 返回距离过期还有多少秒
     */
    public function ttl() {
        //不从SLAVE上拿是因为会不准
        return $this->client()->ttl($this->name);
    }

    public function create() {
        $this->set_ttl($this->ttl());

        return $this;
    }
    /**
     * @return bool
     */
    public function refresh() {
        return $this->set_ttl($this->ttl);
    }

    /**
     * @param int $ttl 过期时间
     *
     * @return int
     */
    public function set_ttl($ttl) {
        return ($ttl > 0) && $this->client()->expire($this->name, $ttl);
    }

    public function returnRandomKey($clientMethod = self::SLAVE) {
        return $this->client($clientMethod)->randomKey();
    }
}
