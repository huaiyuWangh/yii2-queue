<?php

namespace yii2queue\queue;

class Queue extends RedisConnection
{
    public $todoPrefix = 'todo:';

    /**
     * 入队操作
     * @param $queue
     * @param string $msg
     * @return bool
     */
    public function send($queue, $msg = '')
    {
        $msg = serialize($msg);
        //TODO列表中没有数据则入队
        $key = md5($msg);
        if (!$this->getClient()->hExists($this->todoPrefix . $queue, $key)) {
            $this->getClient()->hSet($this->todoPrefix . $queue, $key, 1);
            $this->getClient()->lPush($queue, $msg);
        }

        return true;
    }

    /**
     * 出队操作
     * @param $queue
     * @param int $timeout
     * @return bool|mixed
     */
    public function receive($queue, $timeout = 5)
    {
        $data = $this->getClient()->brPop($queue, $timeout);
        if (isset($data[1])) {
            //从TODO列表中删除
            $key = md5($data[1]);
            $this->getClient()->hDel($this->todoPrefix . $queue, $key);

            return unserialize($data[1]);
        }

        return false;
    }

    /**
     * 获取队列长度
     *
     * @param string $queue
     * @return int
     */
    public function getLength($queue)
    {
        return (int)$this->getClient()->lLen($queue);
    }
}