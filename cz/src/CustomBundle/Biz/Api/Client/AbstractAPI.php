<?php

namespace CustomBundle\Biz\Api\Client;

class AbstractAPI
{
    protected $userAgent = 'EduSoho Czie Client 1.0';

    protected $connectTimeout = 60;

    protected $timeout = 60;

    protected $debug = false;

    /**
     * @var LoggerInterface|null
     */
    protected $logger = null;

    public function post($url, array $params = array(), array $header = array())
    {
        return $this->_request('POST', $url, $params, $header);
    }

    public function put($url, array $params = array(), array $header = array())
    {
        return $this->_request('PUT', $url, $params, $header);
    }

    public function patch($url, array $params = array(), array $header = array())
    {
        return $this->_request('PATCH', $url, $params, $header);
    }

    public function get($url, array $params = array(), array $header = array())
    {
        return $this->_request('GET', $url, $params, $header);
    }

    public function delete($url, array $params = array(), array $header = array())
    {
        return $this->_request('DELETE', $url, $params, $header);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    protected function _request($method, $url, $params, $headers)
    {
        $requestId = substr(md5(uniqid('', true)), -16);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method == 'PATCH') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            if (!empty($params)) {
                $url = $url.(strpos($url, '?') ? '&' : '?').http_build_query($params);
            }
        }

        $headers[] = 'API-REQUEST-ID: '.$requestId;

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);
        $curlinfo = curl_getinfo($curl);

        $header = substr($response, 0, $curlinfo['header_size']);
        $body   = substr($response, $curlinfo['header_size']);

        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] CURL_INFO", $curlinfo);
        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] RESPONSE_HEADER {$header}");
        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] RESPONSE_BODY {$body}");

        curl_close($curl);

        $context = array(
            'CURLINFO' => $curlinfo,
            'HEADER'   => $header,
            'BODY'     => $body
        );

        if (empty($curlinfo['namelookup_time'])) {
            $this->logger && $this->logger->error("[{$requestId}] NAME_LOOK_UP_TIMEOUT", $context);
        }

        if (empty($curlinfo['connect_time'])) {
            $this->logger && $this->logger->error("[{$requestId}] API_CONNECT_TIMEOUT", $context);
            throw new \Exception("Connect api server timeout (url: {$url}).");
        }

        if (empty($curlinfo['starttransfer_time'])) {
            $this->logger && $this->logger->error("[{$requestId}] API_TIMEOUT", $context);
            throw new \Exception("Request api server timeout (url:{$url}).");
        }

        if ($curlinfo['http_code'] >= 500) {
            $this->logger && $this->logger->error("[{$requestId}] API_RESOPNSE_ERROR", $context);
            throw new \Exception("Api server internal error (url:{$url}).");
        }

        if ($this->debug && $this->logger) {
            $this->logger->debug("[{$requestId}] {$method} {$url}", array('params' => $params, 'headers' => $headers));
        }

        return $body;
    }
}
