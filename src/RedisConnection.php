<?php
namespace yii2queue;

use yii\base\Object;

class RedisConnection extends Object
{
    private $_host = '127.0.0.1';
    private $_port = '6379';
    private $_auth = null;
    private $_db = 0;

    public function getClient()
    {
        $client = new \Redis();
        $client->connect($this->_host, $this->_port);
        if (!is_null($this->_auth)) {
            $client->auth($this->_auth);
        }
        $client->select($this->_db);
        return $client;
    }

    public function __set($name, $value)
    {
        $privateProperty =  '_' . $name;
        if (property_exists(__CLASS__, $privateProperty)) {
            $this->$privateProperty = $value;
        } else {
            throw new \Exception('Illegal Property!');
        }
    }

    public function __call($name, $params)
    {
        $client = $this->getClient();
        if (method_exists($client, $name)) {
            return call_user_func([$client, $name], ...$params);
        } else {
            throw new \Exception('Illegal Method!');
        }
    }

    public function test()
    {
        $this->getClient()->zAdd('','','');
    }
}