<?php
namespace CustomBundle\Controller\Weixin;

use Biz\User\CurrentUser;
use CustomBundle\Common\WeixinClient;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class WeixinLoginController extends WeixinBaseController
{
    public function codeAction(Request $request)
    {
        $user = $this->getCurrentUser();

        if ($user->isLogin()) {
            $this->getUserService()->markWeixinLoginInfo();
            $version = $this->container->getParameter("app_version");

            return $this->redirect($this->weixinEntry.'?'.$version);
        }

        $code = $request->query->get('code');
        $client = $this->getPlatformClient();

        if (empty($code)) {
            $goto = $request->query->get('goto');
            $loginPath = $this->generateUrl('weixin_login', array('goto' => $goto));
            $url = $client->getAuthUrl($loginPath);
        } else {
            $result = $client->getUserInfo($code);
            if (empty($result['UserId'])) {
                return $this->render('login-error.html.twig');
            }
            $user = $this->userLogin($request, $result['UserId']);

            $version = $this->container->getParameter("app_version");
            $url = $request->query->get('goto', $this->weixinEntry.'?'.$version);
            $url = urldecode($url);
        }

        return $this->redirect($url);
    }

    public function hostNameAction()
    {
        $name = $this->container->getParameter("hostName");

        return $this->createJsonResponse($name);
    }

    protected function userLogin($request, $nickname)
    {
        $user = $this->getUserService()->getUserByNickname($nickname);
        if (empty($user)) {
            $client = $this->getPlatformClient();
            $userDetail = $client->getUserDetail($nickname);

            $fields = array(
                'nickname' => $userDetail['userid'],
                'password' => $userDetail['userid'],
                'createdIp' => $request->getClientIp(),
                'truename' => empty($userDetail['name']) ? '' : $userDetail['name'],
                'email' => empty($userDetail['email']) ? "{$userDetail['userid']}@czie.edu.cn" : $userDetail['email'],
                'gender'   => $userDetail['gender'] == '1' ? 'male' : 'female',
                'type'     => 'import',
            );
            $user = $this->getAuthService()->register($fields);
            $this->getLogService()->info('user', 'add', "通过微信企业号导入新用户 {$user['nickname']} ({$user['id']})");
        }

        $currentUser = new currentUser();
        $currentUser->fromArray($user);
        $this->switchUser($request, $currentUser);

        return $user;
    }

    public function weixinRedirectAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $host = $request->getSchemeAndHttpHost();
        $goto = $request->query->get('goto');

        if (!$user->isLogin()) {
            $url = $this->generateUrl('weixin_login', array('goto' => $goto));
        } else {
            $url = urldecode($goto);
        }

        return $this->redirect($url);
    }

    public function currentUserAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return $this->createJsonResponse(array('未登录'));
        }
        $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('site')->getValue();

        return $this->createJsonResponse(array(
            'role' => in_array('ROLE_TEACHER', $user['roles']) ? 'teacher' : 'student',
            'userId' => $user['id'],
            'csrf_token' => $csrfToken,
            'host' => $request->getSchemeAndHttpHost(),
        ));
    }

    /**
     * @todo 前端开发路由  后期需删除
     */
    public function userLoginAction(Request $request)
    {
        $result = $request->query->all();
        $user = $this->getUserService()->getUser($result['id']);
        $currentUser = new currentUser();
        $currentUser->fromArray($user);
        $this->switchUser($request, $currentUser);

        $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('site')->getValue();

        return $this->createJsonResponse(array(
            'role' => in_array('ROLE_TEACHER', $user['roles']) ? 'teacher' : 'student',
            'csrf_token' => $csrfToken,
            'host' => $request->getSchemeAndHttpHost(),
        ));
    }

    public function jssdkAction(Request $request)
    {
        $client = $this->getPlatformClient();
        if (!empty($request->query->get('url'))) {
            $jssdk = $client->getJsSDKReport($request->query->get('url'));
        } else {
            $jssdk = $client->getJsSDKParmas();
        }

        return $this->createJsonResponse(
            $jssdk
        );
    }

    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    protected function getAuthService()
    {
        return $this->createService('User:AuthService');
    }
}
