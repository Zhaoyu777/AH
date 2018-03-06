<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\BaseDataTag;
use AppBundle\Extensions\DataTag\DataTag;

class ActivityCourseSetTypeDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['activity'])) {
            if (empty($arguments['courseId'])) {
                return null;
            }

            $course = $this->getCourseService()->getCourse($arguments['courseId']);
            $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        } else {
            $activity = $arguments['activity'];
            $courseSetId = $activity['fromCourseSetId'];

            $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
        }

        if (empty($courseSet)) {
            return null;
        }

        return $courseSet['type'];
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseSetService');
    }
}
