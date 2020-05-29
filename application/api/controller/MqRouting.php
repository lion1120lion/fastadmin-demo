<?php

namespace app\api\controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\Controller;

/**
 * 消息队列路由(绑定routing_key参数，避免与basic_publish参数混淆，将改参数称为“绑定建”)
 * Class MqRouting
 * @package app\api\controller
 */
class MqRouting extends Controller
{
    public function send()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', 123456, '/tbk');
        $channel = $conn->channel();
        $channel->exchange_declare('direct_logs', 'direct', false, false, false);

        $argv = $_SERVER['argv'];
        $severity = empty($argv[2]) ? 'info' : $argv[2];
        $data = 'Hello ,MqRouting';
        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, 'direct_logs', $severity);
        echo "[x]Sent，{$severity} : {$data}\n";
        $channel->close();
        $conn->close();
        exit;
    }

    public function receive()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', 123456, '/tbk');
        $channel = $conn->channel();
        $channel->exchange_declare('direct_logs', 'direct', false, false, false);
        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

        $argv = $_SERVER['argv'];
        $serverities = array_slice($argv, 2);
        if (empty($serverities)) {
            file_put_contents('php://stderr', "Usage:$argv[0] [info] [warning] [error]\n");
            exit(1);
        }

        foreach ($serverities as $serverity) {
            $channel->queue_bind($queue_name, 'direct_logs', $serverity);
        }

        echo "[*] Waiting for logs。To exit press CTRL+C\n";

        $callback = function ($msg) {
            echo "[x],{$msg->delivery_info['routing_key']},:,{$msg->body}\n";
        };

        $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $conn->close();
    }
}