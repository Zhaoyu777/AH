<?php

namespace CustomBundle\Biz\Activity\Service;

interface BrainStormResultService
{
    public function createResult($result);

    public function findResultsByTaskIdAndGroupId($taskId, $groupId);

    public function groupRemark($fields);
}
