<?php

namespace CustomBundle\Biz\Course\Copy;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CourseLessonCopy extends AbstractCopy
{
    private $copys;

    public function __construct()
    {
        $this->copys[] = new LessonTeachingAimsCopy();
        $this->copys[] = new LessonTaskCopy();
        $this->copys[] = new ChapterCopy();
    }

    public function copy($fromId, $toId)
    {
        $fromLesson = $this->getCourseLessonService()->getCourseLesson($fromId);
        $toLesson = $this->getCourseLessonService()->getCourseLesson($toId);

        $fileds = ArrayToolkit::parts($fromLesson, array(
            'title', 'teachAim', 'tasksCase', 'difficult','referenceMaterial', 'afterKnow', 'taskNum'
        ));

        $fileds = array_merge($toLesson, $fileds);
        $lesson = $this->getCourseLessonService()->updateCourseLesson($toLesson['id'], $fileds);

        foreach ($this->copys as $key => $copy) {
            $copy->copy($fromLesson['id'], $toLesson['id']);
        }

        return $lesson;
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
