<?php

namespace Tools\Queue;

use AMQPChannel;
use AMQPConnection;
use Tools\Connection\Connection;
use Tools\Http\HttpClient;

class Queue implements Connection
{
    private $channel;
    private $connection;
    /**
     * @var Exchange[]
     */
    private $exchanges = [];
    /**
     * @var HttpClient
     */
    private $api;
    private $config;

    public function __construct(array $config)
    {
        if (!isset($config['host'])) {
            throw new Exception('host required');
        }
        if (!isset($config['login'])) {
            throw new Exception('login required');
        }
        if (!isset($config['password'])) {
            throw new Exception('password required');
        }

        $connection = new AMQPConnection($config);
        $connection->connect();

        $this->connection = $connection;
        $this->config     = $config;
        $this->channel    = new AMQPChannel($connection);
    }

    public static function normalize(string $name):string
    {
        return strtolower(trim($name));
    }

    public function exchange(string $name, bool $fanOut):Exchange
    {
        $fanOut = (bool)$fanOut;
        $name   = self::normalize($name);
        if (!isset($this->exchanges[$name])) {
            $this->exchanges[$name] = new Exchange($this->channel, $name, $fanOut, $this);
        }

        return $this->exchanges[$name];
    }

    public function api():HttpClient
    {
        if (!isset($this->api)) {
            $config = $this->config;

            $api = new HttpClient("http://{$config['login']}:{$config['password']}@{$config['host']}:15672");
            $api->setTimeout(1);

            $this->api = $api;
        }

        return $this->api;
    }
}
