<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\CourseBaseDataTag;

class CourseMemberCountDataTag extends CourseBaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['courseId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('courseId参数缺失'));
        }
        $arguments['role'] = 'student';

        return $this->getCourseMemberService()->countMembers($arguments);
    }

    protected function getCourseMemberService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:MemberService');
    }
}
