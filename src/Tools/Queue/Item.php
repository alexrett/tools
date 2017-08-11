<?php
namespace Tools\Queue;

use AMQPEnvelope;
use AMQPQueue;
use Tools\Json\Json;

class Item
{
    public  $value;
    private $ev;
    private $queue;
    private $exchange;
    private $result = false;

    public function __construct(AMQPEnvelope $ev, AMQPQueue $queue, Exchange $exchange)
    {
        $this->ev       = $ev;
        $this->queue    = $queue;
        $this->exchange = $exchange;
        $this->value    = Json::decode($ev->getBody());
    }

    public function success():Item
    {
        $this->result();
        if (!$this->queue->ack($this->ev->getDeliveryTag())) {
            throw new Exception('cannot ack');
        }

        return $this->result(true);
    }

    public function failure(bool $requeue = true):Item
    {
        $this->result();
        if (!$this->queue->nack($this->ev->getDeliveryTag(), $requeue ? AMQP_REQUEUE : AMQP_NOPARAM)) {
            throw new Exception('cannot nack');
        }

        return $this->result(true);
    }

    public function requeue():Item
    {
        $this->exchange->push($this->value, $this->ev->getRoutingKey());

        return $this->failure(false);
    }

    /**
     * @param bool $set false - check if result already set, true - set result
     *
     * @return Item
     * @throws Exception
     */
    private function result(bool $set = false):Item
    {
        if ($set) {
            $this->result = true;

            return $this;
        }

        if ($this->result) {
            throw new Exception('already result');
        }

        return $this;
    }
}
