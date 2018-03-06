<?php

namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class BrainStormController extends BaseController
{
    public function resultAction($taskId)
    {
        $user = $this->getCurrentUser();
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $brainStorm = $config->get($activity['mediaId']);

        $groups = $this->buildResults($task, $brainStorm['submitWay']);

        $hasGroup = true;
        if ($brainStorm['groupWay'] == 'random') {
            $groupMember = $this->getTaskGroupService()->getTaskGroupMemberByUserIdAndTaskId($user['id'], $taskId);
            if (empty($groupMember)) {
                $hasGroup = false;
            }
        }

        return $this->createJsonResponse(array(
            'status' => $this->getTaskStatus($taskId),
            'hasGroup' => $hasGroup,
            'submitWay' => $brainStorm['submitWay'],
            'groups' => $groups,
            'isAnswer' => $this->isAnswer($taskId, $user['id']),
        ));
    }

    private function isAnswer($taskId, $userId)
    {
        $user = $this->getCurrentUser();
        $result = $this->getResultService()->getResultByTaskIdAndUserId($taskId, $userId);

        return !empty($result);
    }

    private function buildResults($task, $submitWay)
    {
        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId($task['id']);
        $results = $this->getResultService()->findResultsByTaskId($task['id']);
        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        foreach ($results as $key => $result) {
            $results[$key]['truename'] = $users[$result['userId']]['truename'];
            $results[$key]['number'] = $users[$result['userId']]['number'];
            $results[$key]['avatar'] = $this->getWebExtension()->getFpath($users[$result['userId']]['smallAvatar'], 'avatar.png');
        }

        $results = ArrayToolkit::group($results, 'groupId');
        $response = array();

        if ($submitWay == 'person') {
            $memberCounts = $this->getTaskGroupService()->countTaskGroupMembersByTaskIdGroupByGroupId($task['id']);
            $memberCounts = ArrayToolkit::index($memberCounts, 'groupId');
        }

        foreach ($groups as $key => $group) {
            $response[$key]['groupId'] = $group['id'];
            $response[$key]['title'] = $group['title'];
            $response[$key]['results'] = empty($results[$group['id']]) ? array() : $results[$group['id']];
            $response[$key]['memberCount'] = null;
            $response[$key]['replyCount'] = null;
            if ($submitWay == 'person') {
                $response[$key]['memberCount'] = empty($memberCounts[$group['id']]) ? 0 : $memberCounts[$group['id']]['count'];
                $response[$key]['replyCount'] = count($response[$key]['results']);
            }
        }
        return $response;
    }

    public function joinTaskGroupAction($taskId, $groupId)
    {
        $user = $this->getCurrentUser();

        $member = array(
            'groupId' => $groupId,
            'userId' => $user['id'],
            'taskId' => $taskId,
            'type' => 'random',
        );

        $member = $this->getTaskGroupService()->createTaskGroupMember($member);

        return $this->createJsonResponse(array(
            'status' => $this->getTaskStatus($taskId),
        ));
    }

    public function remarkAction(Request $request, $resultId)
    {
        $fields = $request->query->all();
        $fields['remark'] = explode(',', $fields['remark']);

        $result = $this->getResultService()->remark($resultId, $fields);

        return $this->createJsonResponse(array(
            'score' => $result['score'],
        ));
    }

    public function answerAction(Request $request, $taskId)
    {
        $content = $request->request->get('content');
        $task = $this->getTaskService()->getTask($taskId);
        $user = $this->getCurrentUser();
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $brainStorm = $config->get($activity['mediaId']);

        $groupMember = $this->getTaskGroupService()->getTaskGroupMemberByUserIdAndTaskId($user['id'], $taskId);

        $fields = array(
            'activityId' => $task['activityId'],
            'courseId' => $task['courseId'],
            'courseTaskId' => $task['id'],
            'userId' => $user['id'],
            'groupId' => $groupMember['groupId'],
            'content' => $content,
        );
        if ($brainStorm['submitWay'] == 'person') {
            $result = $this->getResultService()->createResult($fields);
        } else {
            $result = $this->getResultService()->getResultByTaskIdAndGroupId($taskId, $groupMember['groupId']);
            if (empty($result)) {
                $result = $this->getResultService()->createResult($fields);
            } else {
                $result = $this->getResultService()->changeResult($result['id'], $fields);
            }
        }

        return $this->createJsonResponse(array(
            'status' => $this->getTaskStatus($taskId),
            'result' => $result,
        ));
    }

    private function getTaskStatus($taskId)
    {
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        return $status['status'];
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getResultService()
    {
        return $this->createService('CustomBundle:Activity:BrainStormResultService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }
}
