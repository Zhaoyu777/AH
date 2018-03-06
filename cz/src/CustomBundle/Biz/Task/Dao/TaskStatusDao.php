<?php

namespace CustomBundle\Biz\Task\Dao;

interface TaskStatusDao
{
    public function getByTaskId($taskId);

    public function getByActivityId($activityId);
}
