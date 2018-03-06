<?php

namespace CustomBundle\Biz\Practice\Dao;

interface ResultDao
{
    public function findByTaskId($taskId);
}
