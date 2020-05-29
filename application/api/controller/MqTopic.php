<?php

namespace app\api\controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\Controller;

/**
 * (*代表一个单词，#代表零个或多个单词，绑定键按照点分割)
 * Class MqTopic
 * @package app\api\controller
 */
class MqTopic extends Controller
{
    /**
     * 生产者
     * @author lmj
     * @throws \Exception
     */
    public function send()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', 5672, 'tbk', 123456, '/tbk');
        $channel = $conn->channel();
        $channel->exchange_declare('topic_logs', 'topic', false, false, false);

        $argv = $_SERVER['argv'];
        $routing_key = isset($argv[2]) && !empty($argv[2]) ? $argv[2] : 'anonymous.info';
        $data = implode('', array_slice($argv, 3));
        if (empty($data)) {
            $data = "世界你好! ";
        }
        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, 'topic_logs', $routing_key);
        echo "[x]发送，{$routing_key} : {$data}\n";

        $channel->close();
        $conn->close();
        exit;
    }

    /**
     * 消费者
     * @author lmj
     * @throws \ErrorException
     */
    public function receive()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', 5672, 'tbk', 123456, '/tbk');
        $channel = $conn->channel();
        $channel->exchange_declare('topic_logs', 'topic', false, false, false);

        list($queue_name) = $channel->queue_declare('', false, false, true, false);

        $argv = $_SERVER['argv'];
        $binding_keys = array_slice($argv, 2);
        if (empty($binding_keys)) {
            file_put_contents('php://stderr', "用法：{$argv} [0] [binding_key]\n");
            exit;
        }

        foreach ($binding_keys as $binding_key) {
            $channel->queue_bind($queue_name, 'topic_logs', $binding_key);
        }

        echo "[*]等待日志。要退出，请按CTRL+C \n";

        $callback = function ($msg) {
            echo "[x],{$msg->delivery_info['routing_key']} : {$msg->body}\n";
        };
        $channel->basic_consume($queue_name, '', false, false, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $conn->close();
    }
}