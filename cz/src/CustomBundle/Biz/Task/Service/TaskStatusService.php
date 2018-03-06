<?php

namespace CustomBundle\Biz\Task\Service;

interface TaskStatusService
{
    public function startTask($taskId, $activityId);

    public function endTask($taskId, $batchDelete = false);

    public function getStatusByTaskId($taskId);

    public function getStatusByActivityId($activityId);
}
