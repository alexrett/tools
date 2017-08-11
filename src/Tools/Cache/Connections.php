<?php

namespace Tools\Cache;

use Tools\Connection\Connection;
use Tools\Connection\Connections as ConnectionsAbstract;

/**
 * @method Cache connect(string $id = Connections::DEFAULT_ID, string $server = Connections::DEFAULT_SERVER)
 */
class Connections extends ConnectionsAbstract
{
    protected function factory(array $config):Connection
    {
        if (!isset($config['adapter'])) {
            throw new Exception('no adapter');
        }

        $adapter = ucfirst(strtolower($config['adapter']));
        $class   = sprintf('%s\\Cache%s', __NAMESPACE__, $adapter);
        if (!class_exists($class)) {
            throw new Exception("adapter for '$adapter' is undefined");
        }

        $cache = new $class((array)(isset($config['params']) ? $config['params'] : []));
        if (!$cache instanceof Cache) {
            throw new Exception("wrong adapter '$adapter', must be instance of Cache");
        }

        return $cache;
    }
}
