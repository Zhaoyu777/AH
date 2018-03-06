<?php

namespace ApiBundle\Api;

use ApiBundle\Api\Annotation\ApiConf;
use ApiBundle\Api\Exception\ErrorCode;
use ApiBundle\Api\Resource\ResourceManager;
use ApiBundle\Api\Util\ExceptionUtil;
use ApiBundle\ApiBundle;
use Codeages\Biz\Framework\Context\Biz;
use Doctrine\Common\Annotations\CachedReader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResourceKernel
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var CachedReader
     */
    private $annotationReader;

    /**
     * @var Biz
     */
    private $biz;

    /**
     * @var PathParser
     */
    private $pathParser;

    /**
     * @var ResourceManager
     */
    private $resManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->annotationReader = $container->get('annotation_reader');
        $this->biz = $container->get('biz');
        $this->pathParser = $container->get('api.path.parser');
        $this->resManager = $container->get('api.resource.manager');
    }

    public function handle(Request $request)
    {
        $this->parseRequestBody($request);

        $this->container->get('api_firewall')->handle($request);

        $this->biz['user'] = $this->container->get('security.token_storage')->getToken()->getUser();

        if ($this->isBatchRequest($request)) {
            return $this->batchRequest($request);
        } else {
            return $this->singleRequest($request);
        }
    }

    private function parseRequestBody(Request $request)
    {
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
    }

    private function isBatchRequest(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $pathInfo = str_replace(ApiBundle::API_PREFIX, '', $pathInfo);
        return $pathInfo == '/batch';
    }

    private function batchRequest(Request $request)
    {
        $batchArgsRaw = $request->request->get('batch');

        if (!$batchArgsRaw) {
            throw new BadRequestHttpException('缺少参数', null, ErrorCode::INVALID_ARGUMENT);
        }

        $jsonRequests = json_decode($batchArgsRaw, true);
        $this->validateJsonRequests($jsonRequests);

        $result = array();
        foreach ($jsonRequests as $jsonRequest) {
            $apiRequest = $this->makeApiRequestFromJsonRequest($request, $jsonRequest);
            try {
                $successResponse = $this->handleApiRequest($apiRequest);
                $result[] = array(
                    'code' => 200,
                    'body' => $successResponse
                );
            } catch (\Exception $e) {
                list($error, $httpCode) = ExceptionUtil::getErrorAndHttpCodeFromException($e, $this->isDebug());
                $result[] = array(
                    'code' => $httpCode,
                    'body' => array('error' => $error)
                );
            }

        }

        return $result;
    }

    private function isDebug()
    {
        $env = $this->container->get( 'kernel' )->getEnvironment();
        return $env == 'dev';
    }

    private function validateJsonRequests($jsonRequests)
    {
        if (!is_array($jsonRequests)) {
            throw new BadRequestHttpException('batch参数不正确', null, ErrorCode::INVALID_ARGUMENT);
        }

        foreach ($jsonRequests as $jsonRequest) {
            if (empty($jsonRequest['method']) || empty($jsonRequest['relative_url'])) {
                throw new BadRequestHttpException('batch参数不正确', null, ErrorCode::INVALID_ARGUMENT);
            }
        }
    }

    private function singleRequest(Request $request)
    {
        $apiRequest = new ApiRequest($request->getPathInfo(), $request->getMethod(), $request->query, $request->request, $request->headers);
        return $this->handleApiRequest($apiRequest);
    }

    public function handleApiRequest(ApiRequest $apiRequest, $needAuth = true)
    {
        $pathMeta = $this->pathParser->parse($apiRequest);
        $resourceProxy = $this->resManager->create($pathMeta);

        $this->container->get('api_authentication_manager')->authenticate($resourceProxy, $pathMeta->getResMethod());

        return $this->invoke($apiRequest, $resourceProxy, $pathMeta);
    }

    private function invoke($apiRequest, $resource, PathMeta $pathMeta)
    {
        $resMethod = $pathMeta->getResMethod();

        if (!is_callable(array($resource, $resMethod))) {
            throw new BadRequestHttpException('Method does not exist', null, ErrorCode::BAD_REQUEST);
        }

        $params = array_merge(array($apiRequest), $pathMeta->getSlugs());
        return call_user_func_array(array($resource, $resMethod), $params);
    }

    /**
     * @param Request $request
     * @param $jsonRequest
     * @return ApiRequest
     */
    private function makeApiRequestFromJsonRequest(Request $request, $jsonRequest)
    {
        $components = parse_url($jsonRequest['relative_url']);
        $body = array();
        $query = array();

        if (!empty($jsonRequest['body'])) {
            parse_str($jsonRequest['body'], $body);
        }

        if (!empty($components['query'])) {
            parse_str($components['query'], $query);
        }

        $apiRequest = new ApiRequest(
            ApiBundle::API_PREFIX.$components['path'],
            $jsonRequest['method'],
            $query,
            $body,
            $request->headers
        );

        return $apiRequest;
    }
}