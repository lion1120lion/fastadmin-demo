<?php

namespace app\api\controller;

use app\common\controller\Api;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\console\Input;
use think\Controller;

class WorkQueues extends Controller
{
    protected $model = null;

    public function send()
    {
        set_time_limit(0);
        $argv = $_SERVER['argv'];
        $data = implode('', array_slice($argv, 2));
        if (empty($data)) {
            $data = '世界你好！';
        }
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', '123456', '/tbk');
        $channel = $conn->channel();
        $channel->queue_declare('work_queues', false, false, false, false);

        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, '', 'work_queues');
        echo " [x] Sent 'Hello World!'\n";
        $channel->close();
        $conn->close();
        die;
    }

    public function receive()
    {
        set_time_limit(0);
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', '123456', '/tbk');
        $channel = $conn->channel();
        $channel->queue_declare('work_queues', false, false, false, false);
        echo "[*]等待消息。要退出，请按CTRL + C \ n";
        $callback = function ($msg) {
            $time = date('Y-m-d H:i:s');
            echo "时间：{$time}[x]收到，{$msg->body}" . PHP_EOL;
            sleep(substr_count($msg->body, '.'));
            echo "[x]完成\ n" . PHP_EOL;
            $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, false);
        };
        $channel->basic_consume('work_queues', '', false, false, false, false, $callback);
        while ($channel->callbacks) {
            $channel->wait();
        }
        echo "[x]结束\ n";
    }
}