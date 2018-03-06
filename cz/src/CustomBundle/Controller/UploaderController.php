<?php

namespace CustomBundle\Controller;

use Biz\File\Service\UploadFileService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Util\UploaderToken;
use AppBundle\Controller\BaseController;

class UploaderController extends BaseController
{
    public function finishedAction(Request $request)
    {
        $params = $this->parseToken($request);

        if (!$params) {
            return $this->createJsonResponse(array('error' => '授权码不正确，请重试！'));
        }

        $callback = $request->query->get('callback');
        $isJsonp = !empty($callback);
        if ($isJsonp) {
            $params = array_merge($request->query->all(), $params);
        } else {
            $params = array_merge($request->request->all(), $params);
        }

        $params = ArrayToolkit::parts($params, array(
            'id', 'length', 'filename', 'size',
        ));

        $file = $this->getUploadFileService()->finishedUpload($params);
        if ($isJsonp) {
            return $this->createJsonpResponse($file, $callback);
        } else {
            return $this->createJsonResponse($file);
        }
    }

    protected function parseToken(Request $request)
    {
        $token = $request->query->get('token');
        $parser = new UploaderToken();
        $params = $parser->parse($token);

        return $params;
    }

    protected function getUploadFileService()
    {
        return $this->getBiz()->service('CustomBundle:File:UploadFileService');
    }
}