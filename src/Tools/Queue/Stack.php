<?php
namespace Tools\Queue;

use AMQPQueue;

class Stack
{
    private $queue;
    private $exchange;

    public function __construct(AMQPQueue $queue, Exchange $exchange)
    {
        $this->queue    = $queue;
        $this->exchange = $exchange;
    }

    public function cnt():int
    {
        return $this->queue->declareQueue();
    }

    /**
     * @return null|Item
     */
    public function pull()
    {
        if ($ev = $this->queue->get()) {
            return new Item($ev, $this->queue, $this->exchange);
        }

        return null;
    }
}
