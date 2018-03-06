<?php

namespace CustomBundle\Biz\RandomGroup\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\RandomGroup\Service\RandomGroupService;

class RandomGroupServiceImpl extends BaseService implements RandomGroupService
{
    public function createMember($member)
    {
        if (!ArrayToolkit::requireds($member, array('taskId', 'groupNum', 'userId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        if (!empty($this->getMemberByTaskIdAndUserId($member['taskId'], $member['userId']))) {
            throw $this->createInvalidArgumentException('该学生已经加入该次分组');
        }

        $member = ArrayToolkit::parts($member, array(
            'taskId',
            'groupNum',
            'userId'
        ));

        $this->beginTransaction();
        try {
            $created = $this->getRandomGroupDao()->create($member);

            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getMemberByTaskIdAndUserId($taskId, $userId)
    {
         return $this->getRandomGroupDao()->getByTaskIdAndUserId($taskId, $userId);
    }

    public function findMembersByTaskId($taskId)
    {
        return $this->getRandomGroupDao()->findByTaskId($taskId);
    }

    protected function getRandomGroupDao()
    {
        return $this->createDao('CustomBundle:RandomGroup:RandomGroupDao');
    }
}
