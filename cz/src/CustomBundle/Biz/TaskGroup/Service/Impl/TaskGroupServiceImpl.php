<?php

namespace CustomBundle\Biz\TaskGroup\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\TaskGroup\Service\TaskGroupService;

class TaskGroupServiceImpl extends BaseService implements TaskGroupService
{
    public function createTaskGroup($group)
    {
        if (!ArrayToolkit::requireds($group, array('title', 'taskId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $group = ArrayToolkit::parts($group, array(
            'title',
            'taskId',
        ));

        return $this->getGroupDao()->create($group);
    }

    public function createRandomTaskGroups($taskId, $count)
    {
        for ($index = 1; $index <= $count; $index++) {
            $this->getGroupDao()->create(array(
                'title' => "第{$index}组",
                'taskId' => $taskId,
            ));
        }
    }

    public function deleteGroupsByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return null;
        }

        $affected = $this->getGroupDao()->deleteByTaskIds($taskIds);
        $this->getMemberDao()->deleteByTaskIds($taskIds);

        return $affected;
    }

    public function findTaskGroupsByTaskId($taskId)
    {
        return $this->getGroupDao()->findByTaskId($taskId);
    }

    public function createTaskGroupMember($member)
    {
        if (!ArrayToolkit::requireds($member, array('groupId', 'userId', 'taskId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::parts($member, array(
            'groupId',
            'userId',
            'seq',
            'taskId',
        ));

        $created = $this->getMemberDao()->create($fields);
        if (!empty($member['type']) && $member['type'] == 'random') {
            $this->dispatchEvent('random.group.join', new Event($created));
        }

        return $created;
    }

    public function getGroupCaptainByGroupId($groupId)
    {
        return $this->getMemberDao()->getGroupCaptainByGroupId($groupId);
    }

    public function getCaptainByGroupId($groupId)
    {
        return $this->getMemberDao()->getCaptainByGroupId($groupId);
    }

    public function countTaskGroupMembersByTaskIdGroupByGroupId($taskId)
    {
        return $this->getMemberDao()->countByTaskIdGroupByGroupId($taskId);
    }

    public function countTaskGroupMembersByTaskId($taskId)
    {
        return $this->getMemberDao()->count(array('taskId' => $taskId));
    }

    public function countTaskGroupMembersByGroupId($groupId)
    {
        return $this->getMemberDao()->count(array('groupId' => $groupId));
    }

    public function findTaskGroupMembersByGroupId($groupId)
    {
        return $this->getMemberDao()->findByGroupId($groupId);
    }

    public function findTaskGroupMembersByTaskId($taskId)
    {
        return $this->getMemberDao()->findByTaskId($taskId);
    }

    public function getTaskGroupMemberByUserIdAndTaskId($userId, $taskId)
    {
        return $this->getMemberDao()->getByUserIdAndTaskId($userId, $taskId);
    }

    public function getGroup($groupId)
    {
        return $this->getGroupDao()->get($groupId);
    }

    protected function getMemberDao()
    {
        return $this->createDao('CustomBundle:TaskGroup:MemberDao');
    }

    protected function getGroupDao()
    {
        return $this->createDao('CustomBundle:TaskGroup:GroupDao');
    }
}
