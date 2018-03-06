<?php

namespace CustomBundle\Biz\Activity\Strategy\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Activity\Strategy\BaseStrategy;
use CustomBundle\Biz\Activity\Strategy\ResultShowByGroupStrategy;

class FixedPersonStrategy extends BaseStrategy implements ResultShowByGroupStrategy
{
    public function findGroups($task)
    {
        return $this->getGroupService()->findCourseGroupsByCourseIdWithMembers($task['courseId'], true);
    }

    public function findResults($activity, $groups)
    {
        $results = array();
        foreach ($groups as $key => $group) {
            $userIds = ArrayToolkit::column($group['members'], 'userId');
            $results[$group['id']] = $this->getResultService($activity['mediaType'])->findResultsByActivityIdAndUserIdsWithContents($activity['id'], $userIds);
            $results[$group['id']]['contentCount'] = count($results[$group['id']]);
            $results[$group['id']]['memberCount'] = count($group['members']);
        }

        return $results;
    }

    public function getTemplate($activity)
    {
        $template = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-'.strtolower($matches[0]);
        }, $activity['mediaType']);

        return "activity/${template}/show/fixed-submit-person.html.twig";
    }

    public function getLoadTemplate($activity)
    {
        $template = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-'.strtolower($matches[0]);
        }, $activity['mediaType']);

        return "activity/${template}/show/fixed-submit-person-tr.html.twig";
    }

    public function sortWeixinResults($task, $activity, $user)
    {
        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId($task['id']);
        $results = $this->getResultService($activity['mediaType'])->findResultsByTaskId($task['id']);

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
        $totalNumber = count($members);
        $groupMembers = ArrayToolkit::group($members, 'groupId');

        $wallData = array();
        $index = 1;
        $results = ArrayToolkit::group($results, 'groupId');
        foreach ($groups as $key => $group) {
            $wallData[$key] = array(
                'no' => "{$index}组",
                'groupId' => $group['id'],
                'groupTitle' => "{$group['title']}",
                'totalNumber' => $totalNumber,
                'submitNumber' => count($results[$group['id']]),
                'data' => array(),
            );
            if (!empty($results[$group['id']])) {
                $groupResults = $results[$group['id']];
                $data = array();
                foreach ($groupResults as $resultKey => $result) {
                    $data[] = array(
                        'resultId' => $result['id'],
                        'contentId' => $contents[$result['id']]['id'],
                        'avatar' => $this->userAvatar($users[$result['userId']]['smallAvatar']),
                        'name' => empty($users[$result['userId']]['truename']) ? $users[$result['userId']]['nickname'] : $users[$result['userId']]['truename'],
                        'thumb' => $this->getWebExtension()->getFilePath($contents[$result['id']]['uri']),
                        'score' => $result['score'],
                        'isStar' => empty($likes[$contents[$result['id']]['id']]) ? 0 : 1,
                        'likeNum' => $contents[$result['id']]['likeNum'],
                        'postNum' => $contents[$result['id']]['postNum'],
                    );
                }

                $wallData[$key] = array(
                    'no' => "{$index}组",
                    'groupId' => $group['id'],
                    'groupTitle' => "{$group['title']}",
                    'totalNumber' => $totalNumber,
                    'submitNumber' => count($results[$group['id']]),
                    'data' => $data,
                );
                $index ++;
            }
        }
        $status = $this->getStatusService()->getStatusByActivityId($activity['id']);

        return array(
            'status' => empty($status) ? null : $status['status'],
            'groupWay' => 'fixed',
            'submitWay' => 'person',
            'wallData' => array_values($wallData)
        );
    }

    protected function getGroupService()
    {
        return $this->biz->service('CustomBundle:Course:CourseGroupService');
    }

    protected function getTaskGroupService()
    {
        return $this->biz->service('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getUserService()
    {
        return $this->biz->service('User:UserService');
    }
}
