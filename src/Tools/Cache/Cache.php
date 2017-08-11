<?php

namespace Tools\Cache;

use Tools\Connection\Connection;

interface Cache extends Connection
{
    public function setNamespace(string $ns):Cache;

    public function isExist(string $key):bool;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key);

    public function set(string $key, $value, int $ttl = 0):Cache;

    public function delete(string $key):Cache;
}
