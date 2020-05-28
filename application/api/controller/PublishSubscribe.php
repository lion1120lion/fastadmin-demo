<?php

namespace app\api\controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use think\Controller;

/**
 * 发布/订阅
 * Class PublishSubscribe
 * @package app\api\controller
 */
class PublishSubscribe extends Controller
{
    public function send()
    {
        $conn = new AMQPStreamConnection();
    }

    public function receive()
    {
    }
}