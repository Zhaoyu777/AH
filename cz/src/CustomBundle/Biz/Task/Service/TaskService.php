<?php

namespace CustomBundle\Biz\Task\Service;

use Biz\Task\Service\TaskService as BaseTaskService;

interface TaskService extends BaseTaskService
{
    public function findTasksFetchActivityByTaskIds($taskIds);

    public function getLatestTaskResultByCourseIdAndUserIds($courseId, $userIds);

    public function getCurrentTaskByCourseIdAndUserIds($courseId, $userId);

    public function findResultsByTime($startTime, $endTime);

    public function findActiveTasksResultsByCourseId($courseId);

    public function getPreviousTask($taskId);

    public function getNextTask($taskId);

    public function countByTaskType($taskType);
}
