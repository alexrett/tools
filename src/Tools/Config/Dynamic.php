<?php

namespace Tools\Config;

use Tools\Cache\Cache;
use Tools\Filesystem\FileInfo;
use Tools\Filesystem\FileObject;

class Dynamic
{
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var FileInfo
     */
    private $file;
    /**
     * @var FileObject|null
     */
    private $storage;

    final public function __construct(Cache $cache, FileInfo $storage, FileInfo $defaults)
    {
        $this->file  = $storage;
        $this->cache = $cache;

        if (!$cache->isExist(get_class($this))) {
            $vars = ($storage->isExists() ? $storage : $defaults)->returnPhpValue('array');
            foreach ($vars as $name => $value) {
                $cache->set($name, $value);
            }
            $cache->set(get_class($this), 1);

            if (!$storage->isExists()) {
                $this->storage($vars);
            }
        }
    }

    /**
     * @param string     $name
     * @param mixed|null $value
     *
     * @return mixed
     * @throws Exception
     */
    protected function getOrSet(string $name, $value = null)
    {
        $name = $this->name($name);
        if (!$this->cache->isExist($name)) {
            throw new Exception("undefined name '$name', check defaults");
        }

        $cached = $this->cache->get($name);
        if (is_null($value)) {
            return $cached;
        }

        if ($value === $cached) {
            return $cached;
        }

        $vars        = $this->file->returnPhpValue('array');
        $vars[$name] = $value;

        return $this->storage($vars)->cache->set($name, $value)->get($name);
    }

    private function name(string $name):string
    {
        return strtolower(trim($name));
    }

    private function storage(array $vars):Dynamic
    {
        if (is_null($this->storage)) {
            $this->storage = FileObject::factory($this->file, FileObject::MODE_WRITE, true);
        }

        $vars = var_export($vars, true);
        if (is_null($vars)) {
            throw new Exception('cannot export vars to storage (wtf?)');
        }
        $this->storage->lock()->truncate()->write("<?php return $vars;");
        $this->storage->unlock();

        return $this;
    }
}
