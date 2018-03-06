<?php

namespace CustomBundle\Biz\Course\Job;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Scheduler\AbstractJob;

class EndLessonsJob extends AbstractJob
{
    public function execute()
    {
        try {
            $lessons = $this->getCourseLessonService()->findTeachingLessons();
            $lessonIds = array();
            if (!empty($lessons)) {
                $lessonIds = ArrayToolkit::column($lessons, 'id');
            }

            $this->getCourseLessonService()->endLessons($lessonIds);
        } catch (\Exception $e) {
        }
    }

    protected function getCourseLessonService()
    {
        return $this->biz->service('CustomBundle:Course:CourseLessonService');
    }
}
