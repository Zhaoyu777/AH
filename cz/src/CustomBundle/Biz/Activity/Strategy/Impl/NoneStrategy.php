<?php

namespace CustomBundle\Biz\Activity\Strategy\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Activity\Strategy\BaseStrategy;
use CustomBundle\Biz\Activity\Strategy\ResultShowByGroupStrategy;

class NoneStrategy extends BaseStrategy implements ResultShowByGroupStrategy
{
    public function findGroups($task)
    {
        return array();
    }

    public function findResults($activity, $groups)
    {
        return $this->getResultService($activity['mediaType'])->findResultsByActivityIdWithContents($activity['id']);
    }

    public function getTemplate($activity)
    {
        $template = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-'.strtolower($matches[0]);
        }, $activity['mediaType']);

        return "activity/${template}/show/group-none.html.twig";
    }

    public function getLoadTemplate($activity)
    {
        $template = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-'.strtolower($matches[0]);
        }, $activity['mediaType']);

        return "activity/${template}/show/group-none-tr.html.twig";
    }

    public function sortWeixinResults($task, $activity, $user)
    {
        $members = $this->getTaskGroupService()->findTaskGroupMembersByTaskId($task['id']);
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

        $selfData = array(
            'resultId' => null,
            'avatar' => $this->userAvatar($user['smallAvatar']),
            'thumb' => null,
            'likeNum' => 0,
            'postNum' => 0,
        );
        $wallData = array();

        foreach ($results as $key => $result) {
            if (empty($users[$result['userId']])) {
                continue;
            }
            if ($result['userId'] == $user['id']) {
                $selfData = array_merge($selfData, array(
                    'resultId' => $result['id'],
                    'contentId' => $contents[$result['id']]['id'],
                    'thumb' => $this->getWebExtension()->getFilePath($contents[$result['id']]['uri']),
                    'likeNum' => $contents[$result['id']]['likeNum'],
                    'postNum' => $contents[$result['id']]['postNum'],
                ));
                unset($users[$user['id']]);
                continue;
            }

            $wallData[] = array(
                'resultId' => $result['id'],
                'contentId' => $contents[$result['id']]['id'],
                'avatar' => $this->userAvatar($users[$result['userId']]['smallAvatar']),
                'name' => empty($users[$result['userId']]['truename']) ? $users[$result['userId']]['nickname'] : $users[$result['userId']]['truename'],
                'thumb' => $this->getWebExtension()->getFilePath($contents[$result['id']]['uri']) ,
                'score' => $result['score'],
                'isStar' => empty($likes[$contents[$result['id']]['id']]) ? 0 : 1,
                'likeNum' => $contents[$result['id']]['likeNum'],
                'postNum' => $contents[$result['id']]['postNum'],
            );
            unset($users[$result['userId']]);
        }

        array_walk(
            $users,
            function (&$student) use (&$wallData) {
                $wallData[] = array(
                    'resultId' => null,
                    'contentId' => null,
                    'avatar' => $this->userAvatar($student['smallAvatar']),
                    'name' => empty($student['truename']) ? $student['nickname'] : $student['truename'],
                    'thumb' => null,
                    'score' => 0,
                    'isStar' => 0,
                    'likeNum' => 0,
                    'postNum' => 0,
                );
            }
        );
        $status = $this->getStatusService()->getStatusByActivityId($activity['id']);

        return array(
            'status' => empty($status) ? null : $status['status'],
            'groupWay' => 'none',
            'submitWay' => 'person',
            'selfData' => $selfData,
            'wallData' => $wallData
        );
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
