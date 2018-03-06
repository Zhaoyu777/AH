<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class CourseSetAllFilesCountDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['courseSetId'])) {
            return 0;
        }

        return $this->getMaterialService()->countCourseSetAllFilesByCourseSetId($arguments['courseSetId']);
    }

    protected function getMaterialService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:MaterialService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User:UserService');
    }
}
