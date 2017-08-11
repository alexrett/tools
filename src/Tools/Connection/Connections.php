<?php
namespace Tools\Connection;

use Tools\Registry\StaticRegistry;

/**
 * Class Connections
 *
 * @package Tools\Connection
 */
abstract class Connections
{
    use StaticRegistry;

    const DEFAULT_ID     = 'default';

    const DEFAULT_SERVER = 'default';

    /**
     * @var $reg array
     */

    private $config;

    abstract protected function factory(array $config):Connection;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(string $id = self::DEFAULT_ID, string $server = self::DEFAULT_SERVER):Connection
    {
        $class = get_class($this);

        $registryKey = $this->registryKey($class, $id, $server);
        if (!$this->registryTest($registryKey)) {
            if (!isset($this->config[$server])) {
                throw new Exception("no config for server '$server'");
            }

            $this->registrySet($registryKey, $this->factory($this->config[$server]));
        }

        return $this->registryGet($registryKey);
    }
}