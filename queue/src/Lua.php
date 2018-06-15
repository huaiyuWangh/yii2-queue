<?php

namespace yii2queue\queue;
use Yii;

class Lua extends RedisConnection
{
    /**
     * 加锁
     *
     * @param $key
     * @param $cd
     * @return bool
     */
    public function lock($key, $cd)
    {
        //lua脚本
        $lua = <<<LUA
        local key   = KEYS[1]
        local ttl   = ARGV[1]

        local ok = redis.call('setnx', key, 1)
        if ok == 1 then
            redis.call('expire', key, ttl)
        end
        return ok
LUA;

        try {
            $result = $this->getClient()->eval($lua, [$key,$cd], 1);
        }catch (\Exception $e){
            Yii::error($e->getMessage());
            return false;
        }

        return 1 === (int)$result ? true : false;
    }

    /**
     * 解锁
     *
     * @date        2016-05-11
     * @param       [type]           $key             [description]
     *
     * @return bool
     */
    public function unlock($key)
    {
        return $this->getClient()->redis->del($key);
    }

    /**
     * 设置排行
     *
     * @param $key
     * @param $member
     * @param $score
     * @param $time
     */
    public function setRank($key, $member, $score, $time)
    {
        $lua = <<<LUA
            local key, v, score, life_time = KEYS[1], ARGV[1], ARGV[2], ARGV[3]
            
            -- 写入zset, 进行排行
            local ok = redis.call('ZINCRBY', key, score, v)

            -- 设置过期时间
            if (-1 == redis.call('TTL', key)) then
                redis.call('EXPIRE', key, life_time)
            end
            
            return ok
LUA;

        $result = $this->getClient()->eval($lua, [$key, $member, $score,$time], 1);
        return $result;
    }

    /**
     * 获取排行
     * @param $key
     * @param $start
     * @param $end
     * @param bool $rev
     * @param bool $withScores
     * @return array
     */
    public function getRank($key, $start, $end, $rev = false, $withScores = false)
    {
        if (false === $rev) {
            return $this->getClient()->zRange($key, $start, $end, $withScores);
        } else {
            return $this->getClient()->zRevRange($key, $start, $end, $withScores);
        }
    }
}