<?php

namespace Tools\Registry;

trait StaticRegistry
{
    private $registryStorage = [];

    protected function registryGet(string $key)
    {
        if (!$this->registryTest($key)) {
            throw new Exception($key);
        }

        return $this->registryStorage[$key];
    }

    protected function registryTest(string $key):bool
    {
        return array_key_exists($key, $this->registryStorage);
    }

    protected function registrySet(string $key, $value)
    {
        $this->registryStorage[$key] = $value;
    }

    protected function registryKey(string ...$parts):string
    {
        return serialize(array_map('strval', $parts));
    }

    protected function registryKeys():array
    {
        return array_keys($this->registryStorage);
    }
}
