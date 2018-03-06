<?php

namespace AppBundle\Controller;

use AppBundle\Util\AvatarAlert;
use Biz\System\Service\SettingService;
use Biz\User\Service\UserFieldService;
use Symfony\Component\HttpFoundation\Request;

abstract class BuyFlowController extends BaseController
{
    protected $targetType = '';

    public function buyAction(Request $request, $id)
    {
        $this->checkUserLogin();

        if ($this->needApproval($id)) {
            return $this->render('buy-flow/approve-modal.html.twig');
        }

        if ($this->needNoStudentNumTip($id)) {
            return $this->render('buy-flow/no-remain-modal.html.twig');
        }

        if ($this->needUploadAvatar()) {
            return $this->render('buy-flow/avatar-alert-modal.html.twig');
        }

        if ($this->needFillUserInfo()) {
            $userFields = $this->getUserFieldService()->getEnabledFieldsOrderBySeq();
            $user = $this->getUser();
            $userInfo = $this->getUserService()->getUserProfile($user['id']);
            $userInfo['approvalStatus'] = $user['approvalStatus'];

            return $this->render('buy-flow/fill-user-info-modal.html.twig', array(
                'userFields' => $userFields,
                'user' => $userInfo,
            ));
        }

        $this->tryFreeJoin($id);

        if ($this->isJoined($id)) {
            return $this->createJsonResponse(array('url' => $this->getSuccessUrl($id)));
        }

        return $this->createJsonResponse(array('url' => $this->generateUrl('order_show', array('targetId' => $id, 'targetType' => $this->targetType))));
    }

    private function needUploadAvatar()
    {
        return AvatarAlert::alertJoinCourse($this->getUser());
    }

    protected function needFillUserInfo()
    {
        $setting = $this->getSettingService()->get('course');

        if (!empty($setting['buy_fill_userinfo'])) {
            $user = $this->getUser();
            $userInfo = $this->getUserService()->getUserProfile($user['id']);
            $user = array_merge($userInfo, $user->toArray());
            $buyFields = $setting['userinfoFields'];
            foreach ($buyFields as $buyField) {
                if (empty($user[$buyField])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function needNoStudentNumTip($id)
    {
        return false;
    }

    protected function needApproval($id)
    {
        return false;
    }

    private function checkUserLogin()
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return UserFieldService
     */
    protected function getUserFieldService()
    {
        return $this->createService('User:UserFieldService');
    }

    abstract protected function getSuccessUrl($id);

    abstract protected function isJoined($id);

    abstract protected function tryFreeJoin($id);
}
