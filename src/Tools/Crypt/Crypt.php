<?php

namespace Tools\Crypt;

class Crypt
{
    const METHOD        = 'aes-256-cbc';

    const KEY_LENGTH    = 32;

    const SALTED        = 'Salted__';

    const SALTED_LENGTH = 8;

    public static function encrypt(string $password, string $content):string
    {
        $bs = openssl_cipher_iv_length(self::METHOD);
        if ($bs === false) {
            throw new Exception(openssl_error_string());
        }

        $salt = openssl_random_pseudo_bytes($bs - self::SALTED_LENGTH);
        if ($salt === false) {
            throw new Exception(openssl_error_string());
        }

        // Generate OpenSSL compatible KEY and IV
        $d   = '';
        $d_i = '';
        while (strlen($d) < (self::KEY_LENGTH + $bs)) {
            $d_i = md5($d_i . $password . $salt, true);
            $d .= $d_i;
        }
        $key = substr($d, 0, self::KEY_LENGTH);
        $iv  = substr($d, self::KEY_LENGTH, self::KEY_LENGTH + $bs);

        $encrypt = openssl_encrypt(
            (string)$content,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($encrypt === false) {
            throw new Exception(openssl_error_string());
        }
        $raw = sprintf(
            '%s%s%s',
            self::SALTED,
            $salt,
            $encrypt
        );

        return base64_encode($raw);
    }

    public static function decrypt(string $password, string $content):string
    {
        $raw   = base64_decode((string)$content);
        $salt  = substr($raw, 8, 8);
        $keyIv = md5($password . $salt, 1);

        $tmp = $keyIv;
        for ($i = 0; $i < 2; $i++) {
            $tmp = md5($tmp . $password . $salt, true);
            $keyIv .= $tmp;
        }

        $key = substr($keyIv, 0, 32);
        $iv  = substr($keyIv, 32, 16);

        $decrypt = openssl_decrypt(
            substr($raw, 16),
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypt === false) {
            throw new Exception(openssl_error_string());
        }

        return $decrypt;
    }
}
