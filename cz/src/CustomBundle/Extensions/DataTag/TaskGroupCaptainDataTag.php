<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\CourseBaseDataTag;

class TaskGroupCaptainDataTag extends CourseBaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['groupId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('groupId参数缺失'));
        }

        $groupMember = $this->getTaskGroupService()->getCaptainByGroupId($arguments['groupId']);

        $user = array();
        if (!empty($groupMember)) {
            $user = $this->getUserService()->getUser($groupMember['userId']);
        }

        return $user;
    }

    protected function getTaskGroupService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:User:UserService');
    }
}
