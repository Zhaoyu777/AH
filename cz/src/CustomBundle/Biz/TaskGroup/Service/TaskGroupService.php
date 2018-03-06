<?php

namespace CustomBundle\Biz\TaskGroup\Service;

interface TaskGroupService
{
    public function createTaskGroup($group);

    public function createTaskGroupMember($member);

    public function getGroup($groupId);

    public function getTaskGroupMemberByUserIdAndTaskId($userId, $taskId);

    public function findTaskGroupMembersByTaskId($taskId);

    public function findTaskGroupMembersByGroupId($groupId);

    public function countTaskGroupMembersByTaskId($taskId);

    public function getCaptainByGroupId($groupId);

    public function findTaskGroupsByTaskId($taskId);

    public function deleteGroupsByTaskIds($taskIds);

    public function getGroupCaptainByGroupId($groupId);
}
