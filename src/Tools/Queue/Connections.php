<?php

namespace Tools\Queue;

use Tools\Connection\Connection;
use Tools\Connection\Connections as ConnectionsAbstract;

/**
 * @method Queue connect(string $id = Connections::DEFAULT_ID, string $server = Connections::DEFAULT_SERVER)
 */
class Connections extends ConnectionsAbstract
{
    protected function factory(array $config):Connection
    {
        return new Queue($config);
    }
}
