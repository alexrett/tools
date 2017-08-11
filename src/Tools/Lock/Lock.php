<?php

namespace Tools\Lock;

use Tools\Filesystem\FileInfo;
use Tools\Filesystem\FileObject;
use Tools\Filesystem\FileObjectException;

class Lock
{
    /**
     * default path, use global constant LOCK_PREFIX for overwrite
     */
    const DEFAULT_PREFIX = '/run/lock/Tools-lock-';

    protected $id;
    protected $fileObject;
    /**
     * @var Lock[]
     */
    private static $instances = [];

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function factory(string ...$suffix):Lock
    {
        $last = debug_backtrace()[0];
        $id   = md5(serialize([$last['file'], $last['line'], $suffix]));

        if (!isset(self::$instances[$id])) {
            self::$instances[$id] = new self($id);
        }

        return self::$instances[$id];
    }

    public function allocate(int $timeout = 1):bool
    {
        try {
            $this->fileObject()->lock($timeout * 1000000);

            return true;
        } catch (FileObjectException $e) {
            if ($e->getCode() == FileObjectException::LOCK_TIMEOUT) {
                return false;
            }

            throw $e;
        }
    }

    public function release()
    {
        $o = $this->fileObject();
        $o->isLocked() && $o->unlock();
    }

    public function __destruct()
    {
        $this->release();
    }

    private function fileObject():FileObject
    {
        if (is_null($this->fileObject)) {
            $dir = defined('LOCK_PREFIX') ? LOCK_PREFIX : self::DEFAULT_PREFIX;

            $this->fileObject = FileObject::factory(new FileInfo("$dir{$this->id}"), FileObject::MODE_WRITE, true);
        }

        return $this->fileObject;
    }
}
