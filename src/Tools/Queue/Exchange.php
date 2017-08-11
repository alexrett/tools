<?php

namespace Tools\Queue;

use AMQPChannel;
use AMQPExchange;
use AMQPQueue;
use Tools\Json\Json;

class Exchange
{
    public static $prefix = 'Tools.';
    private       $exchange;
    private       $channel;
    private       $queue;
    private       $name;
    /**
     * @var Stack[]
     */
    private $stacks = [];

    public function __construct(AMQPChannel $channel, string $name, bool $fanOut, Queue $queue)
    {
        $name = sprintf('%s%s', self::$prefix, $name);

        $exchange = new AMQPExchange($channel);
        $exchange->setName($name);
        $exchange->setType($fanOut ? AMQP_EX_TYPE_FANOUT : AMQP_EX_TYPE_DIRECT);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declareExchange();

        $this->exchange = $exchange;
        $this->channel  = $channel;
        $this->name     = $name;
        $this->queue    = $queue;

        if (!$fanOut) {
            $this->stack();
        }
    }

    public function isFanOut():bool
    {
        return $this->exchange->getType() == AMQP_EX_TYPE_FANOUT;
    }

    /**
     * @param mixed  $message
     * @param string $rk
     *
     * @return Exchange
     * @throws Exception
     */
    public function push($message, string $rk = ''):Exchange
    {
        if (!$this->exchange->publish(Json::encode($message), $rk ? $rk : null, AMQP_NOPARAM, ['delivery_mode' => 2])) {
            throw new Exception('cannot push message');
        }

        return $this;
    }

    public function stack(string $name = '', string $rk = ''):Stack
    {
        $name = Queue::normalize($name);
        if (!isset($this->stacks[$name])) {
            $queue = new AMQPQueue($this->channel);
            $queue->setName("{$this->name}:$name");
            $queue->setFlags(AMQP_DURABLE);
            $queue->declareQueue();
            $queue->bind($this->exchange->getName(), $rk ? $rk : null);

            $this->stacks[$name] = new Stack($queue, $this);
        }

        return $this->stacks[$name];
    }

    public function stackNames():array
    {
        $response = $this->queue->api()->setUrl('/api/queues')->get();
        if (!$response->isOk()) {
            throw new Exception('cannot get queues info');
        }

        $names = [];
        foreach (Json::decode($response->body()) as $queue) {
            if (strpos($queue['name'], $this->name) === 0) {
                $names[] = str_replace("{$this->name}:", '', $queue['name']);
            }
        }

        return array_filter($names);
    }
}
