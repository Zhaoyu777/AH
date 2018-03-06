<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\CourseBaseDataTag;

class CourseLessonTaskCountDataTag extends CourseBaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['courseId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('courseId参数缺失'));
        }
        if (empty($arguments['lessonId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('lessonId参数缺失'));
        }
        if (empty($arguments['stage'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('stage参数缺失'));
        }

        $unFinishedCount = $this->getCourseLessonService()->countLessonUnfinishedTask($arguments);
        $count = $this->getCourseLessonService()->countLessonTask($arguments);

        return array($unFinishedCount, $count);
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }
}
