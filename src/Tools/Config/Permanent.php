<?php

namespace Tools\Config;

use Tools\Filesystem\FileInfo;
use Tools\Filesystem\FileObject;

class Permanent
{
    protected $config     = [];
    protected $valueCache = [];

    final public function __construct(array $files, string $cacheFile = '')
    {
        if ($cacheFile) {
            $cacheFile = new FileInfo($cacheFile);
            if ($cacheFile->isExists()) {
                $this->load($cacheFile);
            } else {
                foreach ($files as $file) {
                    $this->load(new FileInfo($file));
                }

                $config = var_export($this->config, true);
                if (is_null($config)) {
                    throw new Exception('cannot export config to cache');
                }

                $fo = FileObject::factory(new FileInfo($cacheFile), FileObject::MODE_WRITE, true);
                $fo->lock()->truncate()->write("<?php return $config;");
                $fo->unlock();
            }
        } else {
            foreach ($files as $file) {
                $this->load(new FileInfo($file));
            }
        }
    }

    /**
     * @param string[] ...$path
     *
     * @return mixed
     * @throws Exception
     */
    public function getValue(string ...$path)
    {
        $key = md5(serialize($path));
        if (!array_key_exists($key, $this->valueCache)) {
            $result  = $this->config;
            $strPath = '';
            foreach ($path as $part) {
                if (!is_array($result)) {
                    throw new Exception("'$strPath' must be an array to extract '$part'");
                }

                $strPath .= " => $part";
                if (!array_key_exists($part, $result)) {
                    throw new Exception("undefined value '$strPath'");
                }
                $result = $result[$part];
            }

            $this->valueCache[$key] = $result;
        }

        return $this->valueCache[$key];
    }

    /**
     * @param FileInfo $file
     *
     * @return void
     */
    protected function load(FileInfo $file)
    {
        $this->config = array_replace_recursive($this->config, $file->returnPhpValue('array'));
    }
}
