<?php

namespace App\Service\Integrations;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    protected $connection;

    protected $channel;

    protected $queue;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', '127.0.0.1'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest'),
            heartbeat : 120
        );

        $this->channel = $this->connection->channel();
        $this->queue = env('RABBITMQ_QUEUE', 'whatsapp_queue');

        $this->channel->queue_declare($this->queue, false, true, false, false);
    }

    public function sendMessage(array $data)
    {
        $messageBody = json_encode($data);
        $message = new AMQPMessage($messageBody, ['delivery_mode' => 2]);

        $this->channel->basic_publish($message, '', $this->queue);

    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
