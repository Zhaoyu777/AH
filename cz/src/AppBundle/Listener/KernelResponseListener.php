<?php

namespace AppBundle\Listener;

use Biz\User\Service\UserActiveService;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class KernelResponseListener extends AbstractSecurityDisabledListener
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isSecurityDisabledRequest($this->container, $request)) {
            return;
        }

        $currentUser = $this->getUserService()->getCurrentUser();

        $this->generateUserActiveLog($request);

        $auth = $this->getSettingService()->get('auth');

        if ($currentUser->isLogin() && !in_array('ROLE_SUPER_ADMIN', $currentUser['roles'])
            && isset($auth['fill_userinfo_after_login']) && $auth['fill_userinfo_after_login'] && isset($auth['registerSort'])
        ) {
            $whiteList = $this->getRouteWhiteList();

            if (in_array($request->getPathInfo(), $whiteList) || strstr($request->getPathInfo(), '/admin')
                || strstr($request->getPathInfo(), '/register/submited') || strstr($request->getPathInfo(), '/mapi_v2')
            ) {
                return;
            }

            $isFillUserInfo = $this->checkUserinfoFieldsFill($currentUser);

            if (!$isFillUserInfo) {
                $url = $this->container->get('router')->generate('login_after_fill_userinfo', array('goto' => $this->getTargetPath($request)));

                $response = new RedirectResponse($url);
                $event->setResponse($response);

                return;
            }
        }
    }

    private function generateUserActiveLog(Request $request)
    {
        $session = $request->getSession();

        if (empty($session)) {
            return;
        }

        $activeUserTime = $session->get('active_user_time', 0);

        //当天登录激活
        if ($activeUserTime != strtotime('today')) {
            $currentUser = $this->getUserService()->getCurrentUser();
            $isActiveUser = $this->getUserActiveLogService()->isActiveUser($currentUser->getId());
            if (!$isActiveUser) {
                $this->getUserActiveLogService()->createActiveUser($currentUser->getId());
            }
            $request->getSession()->set('active_user_time', strtotime('today'));
        }
    }

    protected function getRouteWhiteList()
    {
        return array(
            '/fill/userinfo', '/login', '/logout', '/login_check', '/register/mobile/check',
            '/register/email/check', '/login/bind/weixinmob/newset',
            '/login/bind/weixinmob/existbind', '/login/bind/weixinweb/newset',
            '/login/bind/qq/newset', '/login/bind/weibo/newset', '/login/bind/renren/newset',
            '/login/bind/qq/exist', '/login/bind/weibo/exist', '/login/bind/renren/exist',
            '/login/bind/weixinweb/exist', '/login/bind/weixinmob/exist',
            '/login/bind/weixinmob/choose', '/login/bind/weixinmob/changetoexist',
            '/login/bind/qq/new', '/login/bind/weibo/new', '/login/bind/renren/new',
            '/login/bind/weixinmob/new', '/login/bind/weixinweb/new',
            '/partner/discuz/api/notify', '/partner/phpwind/api/notify', '/partner/login', '/partner/logout',
            '/login/weixinmob', '/login/bind/weixinmob/existbind',
            '/captcha_num', '/register/captcha/check', '/edu_cloud/sms_send',
            '/edu_cloud/sms_check/sms_bind',
        );
    }

    protected function generateUrl($router, $params = array(), $withHost = false)
    {
        return $this->container->get('router')->generate($router, $params, $withHost);
    }

    protected function getTargetPath($request)
    {
        if ($request->query->get('goto')) {
            $targetPath = $request->query->get('goto');
        } elseif ($request->getSession()->has('_target_path')) {
            $targetPath = $request->getSession()->get('_target_path');
        } else {
            $targetPath = $request->headers->get('Referer');
        }

        if ($targetPath == $this->generateUrl('login', array(), true)) {
            return $this->generateUrl('homepage');
        }

        $url = explode('?', $targetPath);

        if ($url[0] == $this->generateUrl('partner_logout', array(), true)) {
            return $this->generateUrl('homepage');
        }

        if ($url[0] == $this->generateUrl('password_reset_update', array(), true)) {
            $targetPath = $this->generateUrl('homepage', array(), true);
        }

        if ($url[0] == $this->generateUrl('login_bind_callback', array('type' => 'weixinmob'))
            || $url[0] == $this->generateUrl('login_bind_callback', array('type' => 'weixinweb'))
            || $url[0] == $this->generateUrl('login_bind_callback', array('type' => 'qq'))
            || $url[0] == $this->generateUrl('login_bind_callback', array('type' => 'weibo'))
            || $url[0] == $this->generateUrl('login_bind_callback', array('type' => 'renren'))
            || $url[0] == $this->generateUrl('login_bind_choose', array('type' => 'qq'))
            || $url[0] == $this->generateUrl('login_bind_choose', array('type' => 'weibo'))
            || $url[0] == $this->generateUrl('login_bind_choose', array('type' => 'renren'))
        ) {
            $targetPath = $this->generateUrl('homepage');
        }

        return $targetPath;
    }

    private function checkUserinfoFieldsFill($user)
    {
        $auth = $this->getSettingService()->get('auth');
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $userProfile['email'] = strstr($user['email'], '@edusoho.net') ? '' : $user['email'];
        $userProfile['mobile'] = empty($auth['mobileSmsValidate']) ? $userProfile['mobile'] : $user['verifiedMobile'];

        $isFillUserInfo = true;

        if ($auth['registerSort']) {
            foreach ($auth['registerSort'] as $key => $val) {
                if (!$userProfile[$val]) {
                    $isFillUserInfo = false;
                }
            }
        }

        return $isFillUserInfo;
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

    protected function getSettingService()
    {
        return ServiceKernel::instance()->createService('System:SettingService');
    }

    protected function getUserService()
    {
        return ServiceKernel::instance()->createService('User:UserService');
    }

    /**
     * @return UserActiveService
     */
    private function getUserActiveLogService()
    {
        return ServiceKernel::instance()->createService('User:UserActiveService');
    }
}
