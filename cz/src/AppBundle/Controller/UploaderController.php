<?php

namespace AppBundle\Controller;

use Biz\File\Service\UploadFileService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Util\UploaderToken;

class UploaderController extends BaseController
{
    public function initAction(Request $request)
    {
        $params = $this->parseToken($request);

        if (!$params) {
            return $this->createJsonResponse(array('error' => '上传授权码不正确，请重试！'));
        }

        $callback = $request->query->get('callback');
        $isJsonp = !empty($callback);

        if ($isJsonp) {
            $params = array_merge($request->query->all(), $params);
        } else {
            $params = array_merge($request->request->all(), $params);
        }

        $params['uploadCallback'] = $this->generateUrl('uploader_upload_callback', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $params['processCallback'] = $this->generateUrl('uploader_process_callback', array(), UrlGeneratorInterface::ABSOLUTE_URL);

        $result = $this->getUploadFileService()->initUpload($params);

        $result['uploadProxyUrl'] = $this->generateUrl('uploader_entry', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $result['authUrl'] = $this->generateUrl('uploader_auth', array(), UrlGeneratorInterface::ABSOLUTE_URL);

        if ($isJsonp) {
            return $this->createJsonpResponse($result, $callback);
        } else {
            return $this->createJsonResponse($result);
        }
    }

    public function uploadAuthAction(Request $request)
    {
        $callback = $request->query->get('callback');
        $isJsonp = !empty($callback);

        if ($isJsonp) {
            $params = $request->query->all();
        } else {
            $params = $request->request->all();
        }

        $auth = $this->getUploadFileService()->getUploadAuth($params);

        if ($isJsonp) {
            return $this->createJsonpResponse($auth, $callback);
        } else {
            return $this->createJsonResponse($auth);
        }
    }

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

    public function uploadCallbackAction(Request $request)
    {
        $params = $request->request->all();

        return $this->createJsonResponse(true);
    }

    public function processCallbackAction(Request $request)
    {
        $params = $request->request->all();

        $this->getUploadFileService()->setFileProcessed($params);

        return $this->createJsonResponse(true);
    }

    public function batchUploadAction(Request $request)
    {
        $token = $request->query->get('token');
        $parser = new UploaderToken();
        $params = $parser->parse($token);

        if (!$params) {
            return $this->createJsonResponse(array('error' => '上传授权码不正确，请重试！'));
        }

        return $this->render('uploader/batch-upload-modal.html.twig', array(
            'token' => $token,
            'targetType' => $params['targetType'],
        ));
    }

    public function entryAction(Request $request)
    {
        return new Response('-_-');
    }

    public function chunksStartAction(Request $request)
    {
        $headers = array(
            'Upload-Token: '.$request->headers->get('Upload-Token'),
        );

        $params = $request->request->all();

        $url = $this->setting('developer.cloud_file_server', '').'/chunks/start';

        $result = $this->_post($url, $params, $headers);

        return new Response($result);
    }

    public function chunksFinishAction(Request $request)
    {
        $headers = array(
            'Upload-Token: '.$request->headers->get('Upload-Token'),
        );

        $params = $request->request->all();

        $url = $this->setting('developer.cloud_file_server', '').'/chunks/finish';

        $result = $this->_post($url, $params, $headers);

        return new Response($result);
    }

    protected function parseToken(Request $request)
    {
        $token = $request->query->get('token');
        $parser = new UploaderToken();
        $params = $parser->parse($token);

        return $params;
    }

    protected function _post($url, $params, $headers, $sendAsBinary = false)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        curl_setopt($curl, CURLOPT_POST, 1);

        if ($sendAsBinary) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);
        $curlinfo = curl_getinfo($curl);
        $header = substr($response, 0, $curlinfo['header_size']);
        $body = substr($response, $curlinfo['header_size']);

        curl_close($curl);

        $context = array(
            'CURLINFO' => $curlinfo,
            'HEADER' => $header,
            'BODY' => $body,
        );

        return $body;
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }
}
