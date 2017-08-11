<?php
namespace Tools\Shell;

trait Tools
{
    public static function iamInCLI()
    {
        if (PHP_SAPI != 'cli') {
            throw new Exception('must be CLI');
        }
    }
}
