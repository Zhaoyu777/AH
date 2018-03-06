<?php

namespace CustomBundle\Biz\Activity\Dao;

interface BrainStormResultDao
{
    public function findByTaskId($taskId, $count);

    public function findByTaskIds($taskIds);

    public function getByTaskIdAndGroupId($taskId, $groupId);

    public function findByTaskIdAndGroupId($taskId, $groupId);

    public function getByTaskIdAndUserId($taskId, $userId);
}
