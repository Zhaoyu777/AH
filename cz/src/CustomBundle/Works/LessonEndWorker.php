<?php

namespace CustomBundle\Works;

use Codeages\Plumber\IWorker;

class LessonEndWorker extends AbstractWorker
{
    public function executeProcess($data)
    {
        $this->getCourseLessonService()->endCourseLessonProcess($data['body']['lessonId']);

        return array('code' => IWorker::FINISH);
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
