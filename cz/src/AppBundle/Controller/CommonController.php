<?php

namespace AppBundle\Controller;

use Biz\User\CurrentUser;
use Endroid\QrCode\QrCode;
use Biz\User\Service\UserService;
use Biz\User\Service\TokenService;
use Biz\System\Service\SettingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommonController extends BaseController
{
    public function qrcodeAction(Request $request)
    {
        $text = $request->get('text');
        $qrCode = new QrCode();
        $qrCode->setText($text);
        $qrCode->setSize(250);
        $qrCode->setPadding(10);
        $img = $qrCode->get('png');

        $headers = array(
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qrcode.png"',
        );

        return new Response($img, 200, $headers);
    }

    public function parseQrcodeAction(Request $request, $token)
    {
        $token = $this->getTokenService()->verifyToken('qrcode', $token);
        if (empty($token) || !isset($token['data']['url'])) {
            $content = $this->renderView('default/message.html.twig', array(
                'type' => 'error',
                'goto' => $this->generateUrl('homepage', array(), true),
                'duration' => 1,
                'message' => '二维码已失效，正跳转到首页',
            ));

            return new Response($content, '302');
        }

        if (strpos(strtolower($request->headers->get('User-Agent')), 'kuozhi') > -1) {
            return $this->redirect($token['data']['appUrl']);
        }

        $currentUser = $this->getCurrentUser();

        if (!empty($token['userId']) && !$currentUser->isLogin() && $currentUser['id'] != $token['userId']) {
            $user = $this->getUserService()->getUser($token['userId']);
            $currentUser = new CurrentUser();
            $currentUser->fromArray($user);
            $this->switchUser($request, $currentUser);
        }

        return $this->redirect($token['data']['url']);
    }

    public function crontabAction(Request $request)
    {
        $currentUserToken = $this->container->get('security.token_storage')->getToken();

        try {
            $switchUser = new CurrentUser();
            $switchUser->fromArray($this->getUserService()->getUserByType('system'));

            $this->switchUser($request, $switchUser);
            $this->getSchedulerService()->execute();
            $this->container->get('security.token_storage')->setToken($currentUserToken);

            return $this->createJsonResponse(true);
        } catch (\Exception $e) {
            $this->container->get('security.token_storage')->setToken($currentUserToken);

            return $this->createJsonResponse(false);
        }
    }

    public function mobileQrcodeAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if ($user->isLogin()) {
            $tokenFields = array(
                'userId' => $user['id'],
                'duration' => 3600 * 24 * 30,
                'times' => 1,
            );

            $token = $this->getTokenService()->makeToken('mobile_login', $tokenFields);

            $url = $request->getSchemeAndHttpHost().'/mapi_v2/User/loginWithToken?token='.$token['token'];
        } else {
            $url = $request->getSchemeAndHttpHost().'/mapi_v2/School/loginSchoolWithSite?v=1';
        }

        $qrCode = new QrCode();
        $qrCode->setText($url);
        $qrCode->setSize(215);
        $qrCode->setPadding(10);
        $img = $qrCode->get('png');

        $headers = array('Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="image.png"', );

        return new Response($img, 200, $headers);
    }

    /**
     * @return TokenService
     */
    protected function getTokenService()
    {
        return $this->getBiz()->service('User:TokenService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getBiz()->service('User:UserService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }

    protected function getSchedulerService()
    {
        return $this->getBiz()->service('Scheduler:SchedulerService');
    }
}
