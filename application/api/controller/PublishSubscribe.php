<?php

namespace app\api\controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', 123456, '/tbk');
        $channel = $conn->channel();
        $channel->exchange_declare('logs', 'fanout', false, false, false);

        $argv = $_SERVER['argv'];
        $data = implode('', array_slice($argv, 2));
        if (empty($data)) {
            $data = '信息：世界你好！';
        }
        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, 'logs');
        echo "[x]发送，{$data}\n";
        $channel->close();
        $conn->close();
        exit;
    }

    public function receive()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', 123456, '/tbk');
        $channel = $conn->channel();
        $channel->exchange_declare('logs', 'fanout', false, false, false);
        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
        $channel->queue_bind($queue_name, 'logs');
        echo "[*] Waiting for logs,To exit press CTRL+C\n";
        $callback = function ($msg) {
            echo "[xx],{$msg->body},\n";
        };
        //basic_consumer第四个参数，false标识要确认，true表示消费者不需要确认。
        $channel->basic_consume($queue_name, '', false, true, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $conn->close();
    }
}