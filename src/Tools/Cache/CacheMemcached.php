<?php

namespace Tools\Cache;

use Memcached;

class CacheMemcached implements Cache
{
    /**
     * @var Memcached
     */
    private $memcached;
    private $config;
    private $globalNs;
    private $ns = 'default';

    public function __construct(array $config)
    {
        if (!isset($config['host'])) {
            throw new Exception('host required');
        }
        if (!isset($config['port'])) {
            throw new Exception('port required');
        }
        if (isset($params['namespace'])) {
            $this->globalNs = trim($config['namespace']);
        }

        $this->config = $config;
    }

    public function setNamespace(string $ns):Cache
    {
        $this->ns = trim(strtolower($ns));

        return $this;
    }

    public function isExist(string $key):bool
    {
        $m = $this->memcached();
        $m->get($this->normalizeKey($key));

        return $m->getResultCode() == Memcached::RES_SUCCESS;
    }

    public function get(string $key)
    {
        $m     = $this->memcached();
        $value = $m->get($this->normalizeKey($key));
        if ($m->getResultCode() == Memcached::RES_SUCCESS) {
            return $value;
        }

        throw new Exception("value '$key' not found");
    }

    public function set(string $key, $value, int $ttl = 0):Cache
    {
        $m = $this->memcached();
        if (!$m->set($this->normalizeKey($key), $value, $ttl > 0 ? time() + $ttl : 0)) {
            throw new Exception($m->getResultMessage(), $m->getResultCode());
        }

        return $this;
    }

    public function delete(string $key):Cache
    {
        $m = $this->memcached();
        if (!$m->delete($this->normalizeKey($key))) {
            throw new Exception($m->getResultMessage(), $m->getResultCode());
        }

        return $this;
    }

    protected function normalizeKey(string $key):string
    {
        $ns = $this->ns;
        if (!is_null($this->globalNs)) {
            $ns = "{$this->globalNs}_$ns";
        }

        return sprintf('%s_%s', $ns, md5(strtolower($key)));
    }

    private function memcached():Memcached
    {
        if (!isset($this->memcached)) {
            $port = $this->config['port'];
            $host = $this->config['host'];
            $hKey = "$host:$port";

            $memcached = new Memcached();
            $memcached->addServer($host, $port);
            $stats = $memcached->getStats();
            if (!isset($stats[$hKey]) || ($stats[$hKey]['pid'] <= 0)) {
                throw new Exception("cannot connect to $hKey");
            }

            $this->memcached = $memcached;
        }

        return $this->memcached;
    }
}
