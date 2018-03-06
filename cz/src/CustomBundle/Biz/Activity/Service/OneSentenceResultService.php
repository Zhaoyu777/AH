<?php

namespace CustomBundle\Biz\Activity\Service;

interface OneSentenceResultService
{
    public function createResult($result);

    public function deleteResult($resultId);

    public function deleteResultsByTaskIds($taskIds);

    public function getResult($id);

    public function getResultByTaskIdAndUserId($taskId, $userId);

    public function findResultsByTaskId($taskId);
}
