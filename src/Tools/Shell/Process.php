<?php
namespace Tools\Shell;

use Tools\Filesystem\FileInfo;

class Process
{
    use Tools;

    const BIN_PHP = '/usr/bin/php';

    public static $titlePrefix = 'app:';
    private       $cmd;
    /**
     * @var FileInfo
     */
    private $out;
    /**
     * @var FileInfo
     */
    private $err;
    private $bg   = false;
    private $args = [];
    /**
     * @var resource
     */
    private $process;
    private $pipes = [];

    public function __construct(string $cmd)
    {
        $this->cmd = trim($cmd);
    }

    public static function setTitle(string $title)
    {
        if (!cli_set_process_title(sprintf('%s%s', self::$titlePrefix, $title))) {
            throw new Exception('cannot set process title');
        }
    }

    public static function php(FileInfo $fileInfo):Process
    {
        return new self(sprintf('%s %s', self::BIN_PHP, escapeshellarg($fileInfo->checkReadable()->getPathname())));
    }

    public static function sub():Process
    {
        self::iamInCLI();

        $file = trim($_SERVER['SCRIPT_FILENAME']);
        if ($file[0] == '/') {
            $filename = $file;
        } else {
            if (substr($file, 0, 2) == './') {
                $file = substr($file, 2);
            }
            $filename = sprintf(
                '%s/%s',
                $_SERVER['PWD'],
                $file
            );
        }

        $argv = $_SERVER['argv'];
        array_shift($argv);

        return self::php(new FileInfo($filename))->setArgs($argv);
    }

    public function setArgs(array $args):Process
    {
        $this->checkStart(false)->args = array_values($args);

        return $this;
    }

    public function addArg($value):Process
    {
        $this->checkStart(false)->args[] = $value;

        return $this;
    }

    public function setOut(FileInfo $fileInfo):Process
    {
        $this->checkStart(false)->out = $fileInfo;

        return $this;
    }

    public function setErr(FileInfo $fileInfo):Process
    {
        $this->checkStart(false)->err = $fileInfo;

        return $this;
    }

    public function inBackground(bool $flag = true):Process
    {
        $this->checkStart(false)->bg = $flag;

        return $this;
    }

    public function getPid():int
    {
        return $this->status('pid');
    }

    public function isRunning():bool
    {
        return $this->status('running');
    }

    public function start():Process
    {
        $this->checkStart(false);

        $args = [];
        foreach ($this->args as $k => $v) {
            $args[] = escapeshellarg($v);
        }

        $cmd = sprintf('%s %s', $this->cmd, implode(' ', $args));
        if ($this->bg) {
            $cmd = sprintf(
                '%s 1>>%s 2>>%s &',
                $cmd,
                is_null($this->out) ? '/dev/null' : $this->out->checkWritable()->getPathname(),
                is_null($this->err) ? '/dev/null' : $this->err->checkWritable()->getPathname()
            );

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
        } else {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', is_null($this->out) ? '/dev/null' : $this->out->checkWritable()->getPathname(), 'a'],
                2 => ['file', is_null($this->err) ? '/dev/null' : $this->err->checkWritable()->getPathname(), 'a'],
            ];
        }

        if (!$process = proc_open($cmd, $descriptors, $this->pipes)) {
            throw new Exception("cannot start process: $cmd");
        }

        $this->process = $process;

        return $this;
    }

    public function status(string $field = null)
    {
        if (!$status = proc_get_status($this->checkStart(true)->process)) {
            throw new Exception('cannot read process status');
        }

        if (is_null($field)) {
            return $status;
        }

        $field = strtolower(trim($field));
        if (!isset($status[$field])) {
            throw new Exception("undefined status field '$field'");
        }

        return $status[$field];
    }

    private function checkStart(bool $flag):Process
    {
        if (is_null($this->process) === $flag) {
            throw new Exception('status', (int)!$flag);
        }

        return $this;
    }
}
