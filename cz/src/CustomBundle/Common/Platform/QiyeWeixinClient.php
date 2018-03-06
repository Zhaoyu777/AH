<?php

namespace CustomBundle\Common\Platform;

use AppBundle\Common\CurlToolkit;
use Topxia\Service\Common\ServiceKernel;

class QiyeWeixinClient
{
    private $host;

    private $biz;

    protected $entry;

    private $privateFolder = array('practice-work');

    /**
     * 微信企业号  api
     */
    protected $weixinApi = "https://qyapi.weixin.qq.com/cgi-bin";

    /**
     * 登录  api
     */
    protected $loginApi = "https://open.weixin.qq.com/connect/oauth2/authorize";

    public function __construct($option)
    {
        foreach ($option as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getAuthUrl($loginPath)
    {
        $config = $this->getWeixinParameter();

        $redirect_uri = urlencode($this->host.$loginPath);

        return "{$this->loginApi}?appid={$config['appid']}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
    }

    public function getUserInfo($code)
    {
        $access_token = $this->getAccessToken();
        $url = "{$this->weixinApi}/user/getuserinfo?access_token={$access_token}&code={$code}";

        $result = CurlToolkit::request('GET', $url);

        return $result;
    }

    public function getUserDetail($userId)
    {
        $access_token = $this->getAccessToken();
        $url = "{$this->weixinApi}/user/get?access_token={$access_token}&userid={$userId}";

        $userDetail = CurlToolkit::request('GET', $url);

        return $userDetail;
    }

    public function uploadImg($media_id, $folderName, $flag = 1, $userId = 0)
    {
        try {
            $user = $this->getUser();
            if (!empty($user['id'])) {
                $userId = $user['id'];
            }
            $biz = $this->biz;
            $access_token = $this->getAccessToken();
            $url =  "{$this->weixinApi}/media/get?access_token=$access_token&media_id=$media_id";
            $fileContent = file_get_contents($url);
            if (in_array($folderName, $this->privateFolder)) {
                $permission = 'private';
                $save = $biz['topxia.upload.private_directory'];
            } else {
                $permission = 'public';
                $save = $biz['topxia.upload.public_directory'];
            }

            $dir = '/'.$folderName.'/'.date('Y').'/'.date('m-d');
            $saveDir = $save.$dir;
            if (!is_dir($save.$dir)) {
                mkdir(iconv("UTF-8", "GBK", $saveDir), 0777, true);
            }
            $name = time().$userId.".jpg";
            $src = $save.$dir.'/'.$name ;

            $file = fopen($src, 'w+');
            fwrite($file, $fileContent);
            fclose($file);

            return array(
                'size' => strlen($fileContent),
                'uri' => $permission.':/'.$dir.'/'.$name,
            );
        } catch (\Exception $e) {
            if ($flag > 2) {
                return null;
            }
            $flag ++;
            $this->uploadImg($media_id, $folderName, $flag);
        }
    }

    public function getJsSDKParmas()
    {
        $timestamp = time();
        $nonceStr = "";
        for ($i = 0; $i < 6; $i++) {
            $nonceStr .= chr(mt_rand(33, 126));
        }

        $config = $this->getWeixinParameter();

        $jsapi_ticket = $this->getJsapiTicket();
        $version = $this->getParameter("app_version");
        $host = $this->host.$this->entry.'?'.$version;
        $string1 = "jsapi_ticket=$jsapi_ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$host";

        $signature = sha1($string1);

        return array(
            'appId' => $config['appid'],
            'nonceStr' => $nonceStr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        );
    }

    public function getJsSDKReport($url)
    {
        $timestamp = time();
        $nonceStr = "";
        for ($i = 0; $i < 6; $i++) {
            $nonceStr .= chr(mt_rand(33, 126));
        }

        $config = $this->getWeixinParameter();

        $jsapi_ticket = $this->getJsapiTicket();
        $version = $this->getParameter("app_version");
        $string1 = "jsapi_ticket=$jsapi_ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string1);

        return array(
            'appId' => $config['appid'],
            'nonceStr' => $nonceStr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        );
    }

    public function getJsapiTicket()
    {
        $data = $this->getGenericDataService()->getDataByType('jsapi_ticket');

        if (empty($data)) {
            $access_token = $this->getAccessToken();
            $url = "{$this->weixinApi}/get_jsapi_ticket?access_token=$access_token";
            $result = CurlToolkit::request('GET', $url);
            $data = array(
                'expiredTime' => time() + $result['expires_in'],
                'data' => array('jsapi_ticket' => $result['ticket']),
                'type' => 'jsapi_ticket',
            );

            $data = $this->getGenericDataService()->createData($data);
        }

        return $data['data']['jsapi_ticket'];
    }

    public function getAccessToken()
    {
        $data = $this->getGenericDataService()->getDataByType('access_token');

        if (empty($data)) {
            $weixinConfig = $this->getWeixinParameter();
            $url = "{$this->weixinApi}/gettoken?corpid={$weixinConfig['appid']}&corpsecret={$weixinConfig['corpsecret']}";
            $result = CurlToolkit::request('GET', $url);

            $data = array(
                'expiredTime' => time() + $result['expires_in'],
                'data' => array('access_token' => $result['access_token']),
                'type' => 'access_token',
            );

            $data = $this->getGenericDataService()->createData($data);
        }

        return $data['data']['access_token'];
    }

    public function sendNewsMessage($toids, $articles)
    {
        $access_token = $this->getAccessToken();
        $url = "{$this->weixinApi}/message/send?access_token=$access_token";
        $touser = implode("|", $toids);
        $ftmp = '{
            "touser": "'.$touser.'",
            "toparty": "@all",
            "totag": "@all",
            "msgtype": "news",
            "agentid": 0,
            "news": {
                "articles":[
                    {
                       "title": "'.$articles['title'].'",
                       "description": "'.$articles['description'].'",
                       "url": "'.$articles['url'].'",
                       "picurl": "'.$articles['picurl'].'"
                    }
                ]
            }
        }';

        return CurlToolkit::request('POST', $url, $ftmp);
    }

    public function sendTextMessage($toids, $content)
    {
        $access_token = $this->getAccessToken();
        $url = "{$this->weixinApi}/message/send?access_token=$access_token";
        $touser = implode("|", $toids);
        $ftmp = '{
            "touser": "'.$touser.'",
            "toparty": "@all",
            "totag": "@all",
            "msgtype": "text",
            "agentid": 0,
            "text": {
               "content": "'.$content.'"
            },
            "safe":0
        }';

        return CurlToolkit::request('POST', $url, $ftmp);
    }

    protected function getWeixinParameter()
    {
        return $this->getParameter('weixin');
    }

    protected function getParameter($name)
    {
        return ServiceKernel::instance()->getParameter($name);
    }

    protected function getUser()
    {
        $biz = $this->biz;

        return $biz['user'];
    }

    public function setBiz($biz)
    {
        $this->biz = $biz;
    }

    protected function getGenericDataService()
    {
        return $this->biz->service('CustomBundle:GenericData:GenericDataService');
    }

    public function set($key, $value)
    {
        $this->key = $value;
    }
}
