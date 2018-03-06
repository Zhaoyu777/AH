<?php

namespace CustomBundle\Biz\Activity\Service;

interface RollcallResultService
{
    public function createResult($result);

    public function remarkResult($id, $fields);

    public function getResult($id);

    public function findResults($ids);

    public function findResultsByTaskId($taskId);

    public function getResultByTaskIdAndUserId($taskId, $userId);
}
