<?php

namespace CustomBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\JsonResponse;

class MessageController extends BaseController
{
    public function matchAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();
        $data = array();
        $queryString = $request->query->get('q');
        $findedUsersByNickname = $this->getUserService()->searchUsers(
            array('nickname' => $queryString),
            array('createdTime' => 'DESC'),
            0,
            10);
        $findedFollowingIds = $this->getUserService()->filterFollowingIds($currentUser['id'],
            ArrayToolkit::column($findedUsersByNickname, 'id'));

        $filterFollowingUsers = $this->getUserService()->findUsersByIds($findedFollowingIds);

        foreach ($filterFollowingUsers as $filterFollowingUser) {
            $data[] = array(
                'id' => $filterFollowingUser['id'],
                'nickname' => $filterFollowingUser['number'],
            );
        }

        return new JsonResponse($data);
    }

    public function createAction(Request $request, $toId)
    {
        $user = $this->getCurrentUser();
        $receiver = $this->getUserService()->getUser($toId);
        $message = array('receiver' => $receiver['number']);
        if ($request->getMethod() == 'POST') {
            $message = $request->request->get('message');
            $nickname = $message['receiver'];
            $receiver = $this->getUserService()->getUserByNickname($nickname);
            if (empty($receiver)) {
                throw $this->createNotFoundException('抱歉，该收信人尚未注册!');
            }
            $this->getMessageService()->sendMessage($user['id'], $receiver['id'], $message['content']);

            return $this->redirect($this->generateUrl('message'));
        }

        return $this->render('message/send-message-modal.html.twig', array(
            'message' => $message,
            'userId' => $toId, ));
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getBiz()->service('User:UserService');
    }

    /**
     * @return MessageService
     */
    protected function getMessageService()
    {
        return $this->getBiz()->service('User:MessageService');
    }
}