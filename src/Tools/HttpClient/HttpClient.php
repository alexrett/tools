<?php

namespace Tools\Http;

class HttpClient
{
    const POST = 'POST';

    const GET  = 'GET';

    /**
     * default timeout in seconds
     */
    const TIMEOUT = 10;

    private $resource;
    /**
     * @see parse_url()
     * @var array
     */
    private $url        = [];
    private $headers    = [];
    private $fieldsPost = [];
    private $fieldsGet  = [];

    final public function __construct(string $url)
    {
        $this->setUrl($url);

        $this->init();
    }

    public function setUrl(string $url):HttpClient
    {
        $url = trim($url);
        if (!strlen($url)) {
            throw new Exception('empty url');
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new Exception("cannot parse $url");
        }
        $this->url = array_replace_recursive($this->url, $parts);

        $getFields = [];
        if (array_key_exists('query', $parts)) {
            parse_str($parts['query'], $getFields);
        }
        $this->fieldsGet = $getFields;

        return $this;
    }

    public function setTimeout(int $timeout = self::TIMEOUT):HttpClient
    {
        return $this->setOption(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * @see http://php.net/manual/ru/function.curl-setopt.php
     *
     * @param int                                    $opt   CURL constants
     * @param bool | int | string | array | \Closure $value depends on $opt value
     *
     * @return HttpClient
     * @throws Exception
     */
    public function setOption(int $opt, $value):HttpClient
    {
        if (!curl_setopt($this->resource, $opt, $value)) {
            throw new Exception(curl_error($this->resource), curl_errno($this->resource));
        }

        return $this;
    }

    public function setHeader(string $name, string $value):HttpClient
    {
        $this->headers[strtolower(trim($name))] = (string)$value;

        return $this;
    }

    public function setPostField(string $name, $value):HttpClient
    {
        $this->fieldsPost[(string)$name] = $value;

        return $this;
    }

    public function setGetField(string $name, $value):HttpClient
    {
        $this->fieldsGet[(string)$name] = $value;

        return $this;
    }

    public function post(string $data = null):Response
    {
        $this->setOption(CURLOPT_POSTFIELDS, is_null($data) ? http_build_query($this->fieldsPost) : $data);

        return $this->request(self::POST);
    }

    public function postRaw(string $content, string $type = 'application/octet-stream'):Response
    {
        $this->setHeader('Content-Type', $type);

        return $this->post($content);
    }

    public function get():Response
    {
        return $this->request(self::GET);
    }

    public function request(string $type = self::GET):Response
    {
        $this->setOption(CURLOPT_CUSTOMREQUEST, $type);
        $this->setOption(CURLOPT_URL, $this->generateUrl());

        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = "$name: $value";
        }
        $this->setOption(CURLOPT_HTTPHEADER, $headers);

        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_HEADER, true);

        $result = curl_exec($this->resource);
        if (false === $result) {
            throw new RequestException(curl_error($this->resource), curl_errno($this->resource));
        }

        $info = curl_getinfo($this->resource);
        if (false === $info) {
            throw new RequestException(curl_error($this->resource), curl_errno($this->resource));
        }

        return new Response($result, $info);
    }

    /**
     * reset curl to init state
     */
    public function clear()
    {
        $this->headers    = [];
        $this->fieldsPost = [];
        $this->fieldsPost = [];
        curl_close($this->resource);
        $this->init();
    }

    /**
     * custom constructor
     */
    private function init()
    {
        $resource = curl_init($this->generateUrl());
        if (!$resource) {
            throw new Exception(curl_error($this->resource), curl_errno($this->resource));
        }

        $this->resource = $resource;
        $this->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->setTimeout();
    }

    private function generateUrl():string
    {
        $parts = $this->url;

        if (!isset($parts['scheme']) || !$parts['scheme']) {
            throw new Exception('no scheme');
        }
        if (!isset($parts['host']) || !$parts['host']) {
            throw new Exception('no host');
        }

        $parts['query'] = http_build_query($this->fieldsGet);

        $url = [
            "$parts[scheme]://",
        ];

        $auth = '';
        if (isset($parts['user']) && $parts['user']) {
            $auth = $parts['user'];
        }
        if (isset($parts['pass']) && $parts['pass']) {
            $auth = "$auth:$parts[pass]";
        }
        if ($auth) {
            $url[] = "$auth@";
        }

        $url[] = $parts['host'];
        if (isset($parts['port']) && $parts['port']) {
            $url[] = ":$parts[port]";
        }

        $path = '/';
        if (isset($parts['path']) && $parts['path']) {
            $path = $parts['path'];
        }
        $url[] = $path;

        if (!empty($this->fieldsGet)) {
            $url[] = '?' . http_build_query($this->fieldsGet);
        }

        return implode('', $url);
    }
}
