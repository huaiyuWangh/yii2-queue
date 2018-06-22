<?php

namespace console\controllers;

use yii\console\Controller;
use Yii;

class QueueController extends Controller
{

    public function run()
    {
        //注册queue服务
        $this->server = Yii::$app->queue;

        //设置队列
        $queueKey = 'queue';
        while(true) {
            try {
                $msg = $this->server->receive($queueKey);
                if(!empty($msg)) {
                    //获取到信息时进行逻辑处理
                    //some code ...
                }
            } catch (\Exception $e) {
                Yii::error($e->getMessage());
            }
        }
    }
}
