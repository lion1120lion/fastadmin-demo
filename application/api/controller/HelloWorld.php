<?php

namespace app\api\controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\Controller;

class HelloWorld extends Controller
{
    public function send()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', '123456', '/tbk');
        $channel = $conn->channel();
        $channel->queue_declare('hello_world', false, false, false, false);

        $argv = $_SERVER['argv'];
        $data = implode('', array_slice($argv, 2));
        while ($data--){
            $msg = new AMQPMessage($data);
            $channel->basic_publish($msg, '', 'hello_world');
            echo "[x] Sent 'Hello World!'" . PHP_EOL;
        }
        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, '', 'hello_world');
        echo "[x] Sent 'Hello World!'" . PHP_EOL;
        $channel->close();
        $conn->close();
        exit;
    }

    public function receive()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', '123456', '/tbk');
        $channel = $conn->channel();
        $channel->queue_declare('hello_world', false, false, false, false);
        echo "[*] Waiting for message,To exit press CTRL+C\n";
        $callback = function ($msg) {
            echo "[x] Received {$msg->body}\n";
        };
        $channel->basic_consume('hello_world', '', false, true, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}