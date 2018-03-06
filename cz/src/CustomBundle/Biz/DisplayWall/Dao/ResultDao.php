<?php

namespace CustomBundle\Biz\DisplayWall\Dao;

interface ResultDao
{
    public function findByTaskId($taskId);
}
