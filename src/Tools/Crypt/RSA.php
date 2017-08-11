<?php

namespace Tools\Crypt;

class RSA
{
    const ALG  = 'md5';

    const BITS = 2048;

    private $resource;

    public function __construct()
    {
        $resource = openssl_pkey_new(
            [
                'digest_alg'       => self::ALG,
                'private_key_bits' => self::BITS,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
        if (!$resource) {
            throw new Exception(openssl_error_string());
        }

        $this->resource = $resource;
    }

    public function key():string
    {
        if (!openssl_pkey_export($this->resource, $key)) {
            throw new Exception(openssl_error_string());
        }

        return $key;
    }

    public function pub():string
    {
        if (!$details = openssl_pkey_get_details($this->resource)) {
            throw new Exception(openssl_error_string());
        }

        $buffer = sprintf(
            '%s%s%s%s',
            pack('N', 7),
            'ssh-rsa',
            self::encodeBuffer($details['rsa']['e']),
            self::encodeBuffer($details['rsa']['n'])
        );

        return 'ssh-rsa ' . base64_encode($buffer);
    }

    private static function encodeBuffer(string $buffer):string
    {
        // Some kind of magic
        $len = strlen($buffer);
        if (ord($buffer[0]) & 0x80) {
            $len++;
            $buffer = "\x00$buffer";
        }

        return pack('Na*', $len, $buffer);
    }
}
