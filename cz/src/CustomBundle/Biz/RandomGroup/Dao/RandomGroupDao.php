<?php

namespace CustomBundle\Biz\RandomGroup\Dao;

interface RandomGroupDao
{
    public function getByTaskIdAndUserId($taskId, $userId);
}
