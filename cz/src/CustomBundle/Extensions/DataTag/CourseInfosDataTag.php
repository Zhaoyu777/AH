<?php

namespace  CustomBundle\Extensions\DataTag;

use \AppBundle\Extensions\DataTag\BaseDataTag;
use AppBundle\Extensions\DataTag\DataTag;

class CourseInfosDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['courseId'])) {
            return array();
        }

        return $this->getCourseService()->getCourse($arguments['courseId']);
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseService');
    }
}
