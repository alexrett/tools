<?php

namespace Tools\Http;

class Response
{
    const SUCCESS = 200;

    private $result;
    private $info;
    private $headers;
    private $body;

    public function __construct(string $curlResult, array $curlInfo)
    {
        $this->result = $curlResult;
        $this->info   = $curlInfo;
    }

    public function header(string $name):string
    {
        if (is_null($this->headers)) {
            $this->headers = [];
            $headersParts  = explode("\r\n\r\n", trim(substr($this->result, 0, $this->info['header_size'])));
            $headers       = explode("\r\n", array_pop($headersParts));
            array_shift($headers);
            foreach ($headers as $header) {
                if (strpos($header, ':') === false) {
                    throw new RequestException('invalid header');
                }
                list($key, $value) = explode(':', $header, 2);
                $this->headers[strtolower($key)] = trim($value);
            }
        }

        $name = strtolower(trim($name));
        if (!array_key_exists($name, $this->headers)) {
            throw new RequestException("header '$name' is not set");
        }

        return $this->headers[$name];
    }

    public function body():string
    {
        if (is_null($this->body)) {
            $this->body = substr($this->result, $this->info['header_size']);
        }

        return $this->body;
    }

    public function isOk():bool
    {
        return $this->info['http_code'] == self::SUCCESS;
    }

    public function code():int
    {
        return (int)$this->info['http_code'];
    }

    public function contentType():string
    {
        return trim($this->info['content_type']);
    }
}