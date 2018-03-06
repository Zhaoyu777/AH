<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\BaseDataTag;
use AppBundle\Extensions\DataTag\DataTag;

class StudentTaskResultCountDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException('taskId参数缺失');
        }

        return $this->getTaskService()->countCompleteStudentResultByTaskId($arguments['taskId']);
    }

    protected function getTaskService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseMemberService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:MemberService');
    }
}
