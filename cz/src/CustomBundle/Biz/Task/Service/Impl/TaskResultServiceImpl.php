<?php

namespace CustomBundle\Biz\Task\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Task\Service\TaskResultService;
use Biz\Task\Service\Impl\TaskResultServiceImpl as BaseTaskResultServiceImpl;

class TaskResultServiceImpl extends BaseTaskResultServiceImpl implements TaskResultService
{
    public function findWillAnalyzeCourseIds()
    {
        $time = date('Y-m-d');
        $toTime = strtotime($time);
        $fromTime = $toTime - 86400;

        return $this->getTaskResultDao()->findCourseIdsBetweenFromTimeAndToTime($fromTime, $toTime);
    }

    public function findTaskResultsByTaskIdsAndUserId($taskIds, $userId)
    {
        if (empty($taskIds)) {
            return array();
        }

        return $this->getTaskResultDao()->findByTaskIdsAndUserId($taskIds, $userId);
    }

    public function getResultByCourseIdAndTaskId($courseId, $taskId)
    {
        return $this->getTaskResultDao()->getByCourseIdAndTaskId($courseId, $taskId);
    }

    public function getLastResultByBaseResult($baseResult)
    {
        $result = $this->getTaskResultDao()->getLastResultByCourseIdAndUserId($baseResult['courseId'], $baseResult['userId'], $baseResult['createdTime']);

        if (empty($result)) {
            return $baseResult;
        }

        return $result;
    }
    public function updateGroupTaskResultByTaskIdAndUserIds($taskId, $userIds)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $results = $this->getTaskResultDao()->findByUserIdsAndTaskId($userIds, $taskId);
        $results = ArrayToolkit::index($results, 'userId');
        $this->beginTransaction();
        try {
            foreach ($userIds as $userId) {
                if (!empty($results[$userId])) {
                    $this->updateTaskResult($results[$userId]['id'], array('status' => 'finish'));
                } else {
                    $taskResult = array(
                        'activityId' => $task['activityId'],
                        'courseId' => $task['courseId'],
                        'courseTaskId' => $taskId,
                        'userId' => $userId,
                    );
                    $this->taskFinsh($taskResult);
                }
            }
            $this->commit();

            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function taskFinsh($taskResult)
    {
        ArrayToolkit::requireds($taskResult, array(
            'activityId',
            'courseId',
            'courseTaskId',
            'userId',
        ));

        $taskResult['status'] = 'finish';

        return $this->getTaskResultDao()->create($taskResult);
    }

    public function findByCourseIdAndUserId($courseId, $userId)
    {
        return $this->getTaskResultDao()->findFinishedResultsByCourseIdAndUserId($courseId, $userId);
    }

    protected function getTaskResultDao()
    {
        return $this->createDao('CustomBundle:Task:ResultDao');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }
}
