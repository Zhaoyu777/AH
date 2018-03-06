<?php

namespace CustomBundle\Biz\RandomTestpaper\Dao;

interface RandomTestpaperDao
{
    public function getLastTestpaperByTaskIdAndUserId($taskId, $userId);
}
