<?php

namespace CustomBundle\Biz\Activity\Dao;

interface RollcallResultDao
{
    public function findByTaskId($taskId);

    public function findByIds($ids);
}
