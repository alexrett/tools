<?php
namespace Tools\Shell;

class Output
{
    public static $out     = 'php://stdout';
    public static $err     = 'php://stderr';
    public static $addPath = false;

    public static function message(string $message, ...$vars)
    {
        self::write(self::$out, $message, $vars);
    }

    public static function success(string $message, ...$vars)
    {
        self::write(self::$out, $message, $vars, 32);
    }

    public static function info(string $message, ...$vars)
    {
        self::write(self::$out, $message, $vars, 34);
    }

    public static function notice(string $message, ...$vars)
    {
        self::write(self::$err, $message, $vars, 34);
    }

    public static function error(string $message, ...$vars)
    {
        self::write(self::$err, $message, $vars, 31);
    }

    public static function warning(string $message, ...$vars)
    {
        self::write(self::$err, $message, $vars, 33);
    }

    public static function out(string $message, ...$vars)
    {
        self::write(self::$out, $message, $vars);
    }

    public static function raw(string $message)
    {
        file_put_contents(self::$err, $message, FILE_APPEND);
    }

    /**
     * @param \Throwable $e
     * @param mixed[]    $vars
     */
    public static function exception(\Throwable $e, ...$vars)
    {
        $oldShowPath   = self::$addPath;
        self::$addPath = false;

        self::error(
            sprintf(
                "%s#%d: %s\n## %s(%d)\n%s",
                get_class($e),
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ),
            ...$vars
        );

        self::$addPath = $oldShowPath;
    }

    private static function write(string $path, string $message, array $vars, int $color = 0)
    {
        $vars    = (array)$vars;
        $message = sprintf(
            '%s %d#%5d: %s',
            date('c'),
            posix_getuid(),
            posix_getpid(),
            trim($message)
        );
        if ($color) {
            $message = "\033[1;{$color}m{$message}\033[0m";
        }
        $message .= PHP_EOL;
        if (self::$addPath) {
            $trace = debug_backtrace()[1];
            $message .= sprintf(
                "call in %s(%d)\n",
                $trace['file'],
                $trace['line']
            );
        }
        if (!empty($vars)) {
            $vars = trim(print_r($vars, true));
            $message .= $color ? "\033[0;{$color}m" : '';
            foreach (explode(PHP_EOL, $vars) as $row) {
                $message .= ">\t$row\n";
            }
            $message .= $color ? "\033[0m" : '';
        }

        file_put_contents($path, $message, FILE_APPEND);
    }
}
