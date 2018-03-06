<?php

namespace CustomBundle\Biz\Activity\Strategy\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Activity\Strategy\BaseStrategy;
use CustomBundle\Biz\Activity\Strategy\ResultShowByGroupStrategy;

class FixedGroupStrategy extends BaseStrategy implements ResultShowByGroupStrategy
{
    public function sortWeixinResults($task, $activity, $user)
    {
        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId($task['id']);
        $results = $this->getResultService($activity['mediaType'])->findResultsByTaskId($task['id']);
        $results = ArrayToolkit::index($results, 'groupId');

        $resultIds = ArrayToolkit::column($results, 'id');
        $contents = $this->getResultService($activity['mediaType'])->findContentsByResultIds($resultIds);
        $contents = ArrayToolkit::index($contents, 'resultId');

        $userIds = ArrayToolkit::column($results, 'userId');
        $contentUserIds = ArrayToolkit::column($contents, 'userId');
        $userIds = array_merge($userIds, $contentUserIds);
        $users = $this->getUserService()->findUsersByIds($userIds);

        $contentIds = ArrayToolkit::column($contents, 'id');
        $likes = $this->getResultService($activity['mediaType'])->findLikesByContentIdsAndUserId($contentIds, $user['id']);

        $members = $this->getTaskGroupService()->findTaskGroupMembersByTaskId($task['id']);
        $groupMembers = ArrayToolkit::group($members, 'groupId');
        $wallData = array();
        $index = 1;
        foreach ($groups as $key => $group) {
            $wallData[$key] = array(
                'no' => "{$index}组",
                'resultId' => null,
                'groupTitle' => $group['title'],
                'groupId' => $group['id'],
                'contentId' => null,
                'thumb' => null,
                'score' => null,
                'isStar' => null,
                'likeNum' => 0,
                'postNum' => 0,
                'members' => array(),
            );
            if (empty($results[$group['id']])) {
                continue;
            }
            $result = $results[$group['id']];
            $members = array();
            foreach ($groupMembers[$group['id']] as $key => $member) {
                if (empty($users[$member['userId']])) {
                    continue;
                }
                $members[] = array(
                    'userId' => $member['userId'],
                    'avatar' => $this->userAvatar($users[$member['userId']]['smallAvatar']),
                    'name' => empty($users[$member['userId']]['truename']) ? $users[$member['userId']]['nickname'] : $users[$member['userId']]['truename'],
                );
            }

            $wallData[$key] = array(
                'no' => "{$index}组",
                'resultId' => $result['id'],
                'groupId' => $group['id'],
                'contentId' => $contents[$result['id']]['id'],
                'groupTitle' => "{$group['title']}",
                'thumb' => $this->getWebExtension()->getFilePath($contents[$result['id']]['uri']),
                'score' => $result['score'],
                'isStar' => empty($likes[$contents[$result['id']]['id']]) ? 0 : 1,
                'likeNum' => $contents[$result['id']]['likeNum'],
                'postNum' => $contents[$result['id']]['postNum'],
                'members' => $members,
            );
            $index ++;
        }

        $status = $this->getStatusService()->getStatusByActivityId($activity['id']);

        return array(
            'status' => empty($status) ? null : $status['status'],
            'groupWay' => 'fixed',
            'submitWay' => 'group',
            'wallData' => array_values($wallData)
        );
    }

    protected function getUserService()
    {
        return $this->biz->service('User:UserService');
    }

    protected function getTaskGroupService()
    {
        return $this->biz->service('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getGroupService()
    {
        return $this->biz->service('CustomBundle:Course:CourseGroupService');
    }
}
