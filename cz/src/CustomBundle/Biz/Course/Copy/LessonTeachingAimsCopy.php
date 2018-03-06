<?php

namespace CustomBundle\Biz\Course\Copy;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LessonTeachingAimsCopy extends AbstractCopy
{
    public function copy($fromLessonId, $toLessonId)
    {
        $fromLessonAims = $this->getTeachingAimService()->findByLessonId($fromLessonId);

        $toLesson = $this->getCourseLessonService()->getCourseLesson($toLessonId);
        $toCourse = $this->getCourseService()->getCourse($toLesson['courseId']);
        $toCourseSet = $this->getCourseSetService()->getCourseSet($toCourse['courseSetId']);
        $this->getTeachingAimService()->deleteAimsByLessonId($toLessonId);

        $teachingAimFields = array();
        foreach ($fromLessonAims as $lessonAim) {
            $teachingAimFields[] = array(
                'courseId' => $toLesson['courseId'],
                'orgCode' => $toCourseSet['orgCode'],
                'lessonId' => $toLessonId,
                'number' => $lessonAim['number'],
                'type' => $lessonAim['type'],
                'content' => $lessonAim['content'],
                'termCode' => $toCourse['termCode'],
                'parentId' => $lessonAim['id'],
            );
        }

        return $this->getTeachingAimService()->batchCreate($teachingAimFields);
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->getServiceKernel()->createService('Course:CourseSetService');
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTeachingAimService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Lesson:TeachingAimService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
