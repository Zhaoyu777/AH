<?php

namespace CustomBundle\Common;

use Topxia\Service\Common\ServiceKernel;

class PushMsgToolkit
{
    protected $userAgent = 'EduSoho Push Client 1.0';

    protected $connectTimeout = 15;

    protected $timeout        = 15;
    private static $_instance = null;

    private $lifetime = 1200;
    private $algo     = 'sha1';

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function getPushToken(array $data)
    {
        $body      = $this->serialize($data);
        $body      = base64_encode($body);
        $secretKey = $this->getParameter('secret');
        $now       = time();
        $deadline  = $now + $this->lifetime;
        $deadline  = $deadline * 1000;
        $requestId = $this->makeRequestId();
        $token     = $this->packToken($secretKey, '', $body, $deadline, $requestId);
        $token     = $body.':'.$token;
        return array(
            'token'    => $token,
            'lifetime' => $this->lifetime - 60
        );
    }

    public function sendMsg($cmd, $to, $data)
    {
        $body = array(
            'cmd'  => $cmd,
            'to'   => $to,
            'data' => $data
        );
        return $this->_request('POST', '/forward', $body, array());
    }

    protected function serialize($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException("In json hmac specification serialize data must be array.");
        }

        ksort($data);

        $json = json_encode($data);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                'json_encode error: '.json_last_error_msg()
            );
        }

        return $json;
    }

    protected function getParameter($name)
    {
        return ServiceKernel::instance()->getParameter($name);
    }

    protected function makeRequestId()
    {
        return ((string) (microtime(true) * 10000)).substr(md5(uniqid('', true)), -18);
    }

    protected function makeUrl($uri)
    {
        $config   = $this->getParameter('push_server');
        $endpoint = $config['protocol'].'://'.$config['host'].':'.$config['port'];
        return rtrim($endpoint, "\/").$uri;
    }

    protected function makeSignatureUri($url)
    {
        preg_match('/\/\/.*?(\/.*)/', $url, $matches);
        return $matches[1];
    }

    protected function makeHeaders($token, $requestId = '')
    {
        $headers   = array();
        $headers[] = 'Content-type: application/json';
        $headers[] = "X-Auth-Token: {$token}";
        $headers[] = "X-Request-Id: {$requestId}";
        return $headers;
    }

    public function packToken($secretKey, $url, $body, $deadline, $once)
    {
        $signature = $this->signature($secretKey, $url, $body, $deadline, $once);
        return "{$deadline}:{$once}:{$signature}";
    }

    public function signature($secretKey, $url, $body, $deadline, $once)
    {
        $data      = implode("\n", array($url, $deadline, $once, $body));
        $signature = hash_hmac($this->algo, $data, $secretKey, true);
        $signature = str_replace(array('+', '/'), array('-', '_'), base64_encode($signature));
        return $signature;
    }

    protected function _request($method, $uri, $params, $headers)
    {
        $requestId = $this->makeRequestId();
        $url       = $this->makeUrl($uri);
        $body      = ($method == 'GET') || empty($params) ? '' : $this->serialize($params);
        $secretKey = $this->getParameter('secret');
        $now       = time();
        $deadline  = $now + $this->lifetime;
        $deadline  = $deadline * 1000;
        $token     = $this->packToken($secretKey, $this->makeSignatureUri($url), $body, $deadline, $requestId);
        $headers   = array_merge($this->makeHeaders($token, $requestId), $headers);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($method == 'PATCH') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } else {
            if (!empty($params)) {
                $url = $url.(strpos($url, '?') ? '&' : '?').http_build_query($params);
            }
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);
        $curlinfo = curl_getinfo($curl);

        $header = substr($response, 0, $curlinfo['header_size']);
        $body   = substr($response, $curlinfo['header_size']);

        curl_close($curl);

        $context = array(
            'CURLINFO' => $curlinfo,
            'HEADER'   => $header,
            'BODY'     => $body
        );

        if (empty($curlinfo['connect_time'])) {
            throw new \Exception("Connect api server timeout (url: {$url}).");
        }

        if (empty($curlinfo['starttransfer_time'])) {
            throw new \Exception("Request api server timeout (url:{$url}).");
        }

        if ($curlinfo['http_code'] >= 500) {
            throw new \Exception("Api server internal error (url:{$url}).");
        }

        $result = json_decode($body, true);

        if (is_null($result)) {
            throw new \Exception("Api result json decode error: (url:{$url}).");
        }

        return $result;
    }
}
