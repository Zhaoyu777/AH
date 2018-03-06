<?php

namespace CustomBundle\Biz\RandomTestpaper\Service;

interface RandomTestpaperService
{
    public function createTestpaper($testpaper);

    public function getLastTestpaperByTaskIdAndUserId($taskId, $userId);
}
