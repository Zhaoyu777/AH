<?php

namespace CustomBundle\Biz\Task\Service;

use Biz\Task\Service\TaskResultService as BaseTaskResultService;

interface TaskResultService extends BaseTaskResultService
{
    public function findWillAnalyzeCourseIds();

    public function getResultByCourseIdAndTaskId($courseId, $taskId);

    public function getLastResultByBaseResult($baseResult);
}
