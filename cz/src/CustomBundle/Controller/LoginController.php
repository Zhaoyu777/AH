<?php

namespace CustomBundle\Controller;

use AppBundle\Controller\LoginController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use AppBundle\Component\OAuthClient\OAuthClientFactory;

class LoginController extends BaseController
{
    public function indexAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if ($user->isLogin()) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(Security::AUTHENTICATION_ERROR);
        }

        if ($this->getWebExtension()->isMicroMessenger() && $this->setting('login_bind.enabled', 0) && $this->setting('login_bind.weixinmob_enabled', 0)) {
            $inviteCode = $request->query->get('inviteCode', '');

            return $this->redirect($this->generateUrl('login_bind', array('type' => 'weixinmob', '_target_path' => $this->getTargetPath($request), 'inviteCode' => $inviteCode)));
        }

        return $this->render('login/index.html.twig', array(
            'last_username' => $request->getSession()->get(Security::LAST_USERNAME),
            'error' => $error,
            '_target_path' => $this->getTargetPath($request),
        ));
    }
}
