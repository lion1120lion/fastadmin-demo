<?php

namespace app\common\service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class FibonacciRpcClientService
{
    private $conn;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;

    public function __construct()
    {
        $this->conn = new AMQPStreamConnection('192.168.50.248', '5672', 'tbk', '123456', '/tbk');
        $this->channel = $this->conn->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare('', false, false, true, false);
        $this->channel->basic_consume($this->callback_queue, '', false, true, false, false, [$this, 'onResponse']);
    }

    public function onResponse($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($n)
    {
        $this->response = null;
        $this->corr_id = uniqid();
        $msg = new AMQPMessage((string)$n, [
            'correlation_id' => $this->corr_id,
            'reply_to' => $this->callback_queue
        ]);
        $this->channel->basic_publish($msg, '', 'rpc_queue');
        while (!$this->response) {
            $this->channel->wait();
        }
        return intval($this->response);
    }
}