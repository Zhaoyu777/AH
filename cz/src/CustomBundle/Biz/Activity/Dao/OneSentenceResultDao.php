<?php

namespace CustomBundle\Biz\Activity\Dao;

interface OneSentenceResultDao
{
    public function getByTaskIdAndUserId($taskId, $userId);

    public function findByTaskIds($taskIds);

    public function findByTaskId($taskId);

    public function findByIds($ids);
}
