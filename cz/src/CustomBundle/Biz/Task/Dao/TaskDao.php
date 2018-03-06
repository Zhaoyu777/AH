<?php

namespace CustomBundle\Biz\Task\Dao;

interface TaskDao
{
    public function getByCategoryId($categoryId);

    public function findTasksByTaskType($taskType);
}
