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
        local value = ARGV[1]
        local ttl   = ARGV[2]

        local ok = redis.call('setnx', key, value)
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
     * 计数
     *
     * @param $key
     * @param $info
     * @param $time
     */
    public function count($key, array $info, $time)
    {
        $lua = <<<LUA
            local key, args, life_time = KEYS[1], ARGV[1], ARGV[2]
            -- 
            args = cjson.decode(args)
            -- 写入zset, 每个重复的member会自增1
            for k, v in ipairs(args) do
                local redis.call('ZINCRBY', key, 1, v)
            end
            
            -- 设置5小时过期
            if (-1 == redis.call('TTL', key)) then
                local ok = redis.call('EXPIRE', key, life_time)
            end
            
            
            return 'succ'
LUA;

        $result = $this->getClient()->eval($lua, [$key, json_encode($info), $time], 1);
    }
}