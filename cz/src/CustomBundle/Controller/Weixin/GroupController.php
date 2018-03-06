<?php

namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Group\Service\GroupService;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Controller\Weixin\WeixinBaseController;

class GroupController extends WeixinBaseController
{
    public function groupsAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $type = $request->query->get('type', "default");
        $conditions = array(
            'status' => 'open',
            'title' => $request->query->get('keyword', '')
        );
        $orderBy = array();
        if ($type == 'hot') {
            $orderBy['memberNum'] = 'DESC';
        }

        $total = $this->getGroupService()->searchGroupsCount($conditions);
        $groups = $this->getGroupService()->searchGroups(
            $conditions,
            $orderBy,
            ($page - 1) * $limit,
            $limit
        );

        $paging = array(
            'total' => ceil($total/$limit),
            'page' => $page,
            'limit' => $limit
        );

        return $this->createJsonResponse(array(
            'paging'=> $paging,
            'data' => $this->sortGroups($groups)
        ));
    }

    public function groupDetailAction($groupId)
    {
        $user = $this->getCurrentUser();
        $group = $this->getGroupService()->getGroup($groupId);

        unset($group['createdTime']);
        unset($group['ownerId']);

        $group['logo'] = $this->getWebExtension()->getFilePath($group['logo'], 'group.png');
        $group['backgroundLogo'] = $this->getWebExtension()->getFilePath($group['backgroundLogo'], 'background_group.jpg');
        $group['isMember'] = $this->getGroupService()->isMember($groupId, $user['id']);

        return $this->createJsonResponse($group);
    }

    public function myGroupsAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 20);
        $conditions = array('userId' => $user['id']);

        $total = $this->getGroupService()->countMembers($conditions);

        $members = $this->getGroupService()->searchMembers(
            $conditions,
            array(),
            ($page - 1) * $limit,
            $limit
        );

        $groupIds = ArrayToolkit::column($members, 'groupId');
        $groups = $this->getGroupService()->getGroupsByIds($groupIds);

        $paging = array(
            'total' => ceil($total/$limit),
            'page' => $page,
            'limit' => $limit
        );

        return $this->createJsonResponse(array(
            'paging'=> $paging,
            'data' => $this->sortGroups($groups)
        ));
    }

    public function groupMembersAction(Request $request, $groupId)
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 20);
        $total = $this->getGroupService()->getMembersCountByGroupId($groupId);

        $members = $this->getGroupService()->searchMembers(
            array('groupId' => $groupId),
            array(),
            ($page - 1) * $limit,
            $limit
        );

        $userIds = ArrayToolkit::column($members, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $result = array();
        foreach ($members as $key => $member) {
            if (isset($users[$member['userId']])) {
                $result[] = array(
                    'id' => $member['id'],
                    'groupId' => $member['groupId'],
                    'truename' => $users[$member['userId']]['truename'],
                    'nickname' => $users[$member['userId']]['nickname'],
                    'avatar' => $this->getWebExtension()->getFpath($users[$member['userId']]['smallAvatar'], 'avatar.png')
                );
            }
        }

        $paging = array(
            'total' => ceil($total/$limit),
            'page' => $page,
            'limit' => $limit
        );

        return $this->createJsonResponse(array(
            'paging'=> $paging,
            'data' => $result
        ));
    }

    public function joinGroupAction(Request $request, $groupId)
    {
        $user = $this->getCurrentUser();

        $member = $this->getGroupService()->joinGroup($user, $groupId);

        return $this->createJsonResponse(true);
    }

    protected function sortGroups($groups)
    {
        $result = array();
        foreach ($groups as $key => $group) {
            unset($group['createdTime']);
            unset($group['ownerId']);
            $group['logo'] = $this->getWebExtension()->getFilePath($group['logo'], 'group.png');
            $group['backgroundLogo'] = $this->getWebExtension()->getFilePath($group['backgroundLogo'], 'background_group.jpg');
            $result[] = $group;
        }

        return $result;
    }

    protected function getGroupService()
    {
        return $this->createService('Group:GroupService');
    }
}
