<?php

namespace CustomBundle\Biz\RaceAnswer\Service;

interface RaceAnswerService
{
    public function createResult($result);

    public function deleteResult($resultId);

    public function deleteResultsByTaskIds($taskIds);

    public function remarkResult($id, $fields);

    public function findResultsByUserIdsAndTaskId($userIds, $taskId);

    public function getResult($id);

    public function findResultByTaskId($taskId, $count);

    public function getResultByUserIdAndTaskId($userId, $taskId);
}
