<?php

namespace CustomBundle\Biz\RandomGroup\Service;

interface RandomGroupService
{
    public function createMember($member);

    public function getMemberByTaskIdAndUserId($taskId, $userId);
}
