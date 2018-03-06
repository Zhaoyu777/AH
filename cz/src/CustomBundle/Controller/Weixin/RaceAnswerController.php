<?php

namespace CustomBundle\Controller\Weixin;

use CustomBundle\Controller\Weixin\WeixinBaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Activity\ActivityActionInterface;

class RaceAnswerController extends WeixinBaseController
{
    public function startAction($taskId, $activityId)
    {
        $status = $this->getStatusService()->startTask($taskId, $activityId);

        return $this->createJsonResponse($status['status']);
    }

    public function endAction($taskId)
    {
        $status = $this->getStatusService()->endTask($taskId);

        return $this->createJsonResponse($status['status']);
    }

    public function raceAnswerAction($activityId, $courseId, $taskId)
    {
        $user = $this->getCurrentUser();

        $resutl = array(
            'activityId' => $activityId,
            'courseId' => $courseId,
            'courseTaskId' => $taskId,
            'userId' => $user['id'],
        );

        $this->getRaceAnswerService()->createResult($resutl);

        return $this->createJsonResponse(true);
    }

    public function raceResultAction($taskId)
    {
        $receResults = $this->getRaceAnswerService()->findResultByTaskId($taskId);
        $receResults = ArrayToolkit::index($receResults, 'userId');

        $userIds = ArrayToolkit::column($receResults, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $results = array();
        foreach ($receResults as $receResult) {
            $results[] = array(
                'avatar' => $this->getWebExtension()->getFpath($users[$receResult['userId']]['smallAvatar'], 'avatar.png'),
                'resultId' => $receResult['id'],
                'userId' => $users[$receResult['userId']]['id'],
                'nickname' => $users[$receResult['userId']]['nickname'],
                'truename' => $users[$receResult['userId']]['truename'],
                'score' => $receResult['score'],
                'createdTime' => date("y/m/d H:i:s", $receResult['createdTime']),
            );
        }

        $status = $this->status($taskId);
        $result = array(
            'status' => $status['status'],
            'time' => $status['status'] == 'start' ? $this->raceAnswerTime($status['createdTime']) : -1,
            'results' => $results,
        );

        return $this->createJsonResponse($result);
    }

    public function remarkResultAction(Request $request, $resultId, $courseId)
    {
        $data = $request->query->all();
        $user = $this->getCurrentUser();

        $remark = explode(',', $data['remark']);
        $fields = array(
            'score' => $data['score'],
            'remark' => $remark
        );

        $raceAnswer = $this->getRaceAnswerService()->remarkResult($resultId, $fields);

        return $this->createJsonResponse($raceAnswer['score']);
    }

    protected function status($taskId)
    {
        $user = $this->getCurrentUser();
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        $countStudentNum = $this->getRaceAnswerService()->countStudentNumByTaskId($taskId);
        $result = $this->getRaceAnswerService()->getResultByUserIdAndTaskId($user['id'], $taskId);

        if ($countStudentNum >= 10 || !empty($result)) {
            $status['status'] = 'end';
        }

        return $status;
    }

    protected function raceAnswerTime($time)
    {
        $timeLag = 5 - time() + $time;

        return $timeLag > 1 ? $timeLag : 0;
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getRaceAnswerService()
    {
        return $this->createService('CustomBundle:RaceAnswer:RaceAnswerService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }
}
