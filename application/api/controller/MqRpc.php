<?php

namespace app\api\controller;

use app\common\service\FibonacciRpcClientService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\Controller;

/**
 * （需要在远程计算机上运行功能并等待结果过程，称为“远程过程调用”）
 * Class MqRpc
 * @package app\api\controller
 */
class MqRpc extends Controller
{
    /**
     * 斐波那契任务
     * @param $n
     * @author lmj
     * @return int
     */
    public function fib($n)
    {
        if ($n == 0) return 0;
        if ($n == 1) return 1;
        return $this->fib($n - 1) + $this->fib($n - 2);
    }

    public function rpc_server()
    {
        $conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', '123456', '/tbk');
        $channel = $conn->channel();

        $channel->queue_declare('rpc_queue', false, false, false, false);

        echo "[x]等待RPC请求\n";
        $callback = function ($req) {
            $n = intval($req->body);
            $res = $this->fib($n);
            echo "[x]fib方法的参数{$n}\n";

            $msg = new AMQPMessage((string)$this->fib($n), ['correlation_id' => $req->get('correlation_id')]);

            $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));
            $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $conn->close();

    }

    public function clent()
    {
        $fibonacci_rpc = new FibonacciRpcClientService();
        $response = $fibonacci_rpc->call(30);
        echo "[x] Got {$response},\n";
    }
}