<?php

namespace CustomBundle\Biz\Course\Copy;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TaskAimsConnectCopy extends AbstractCopy
{
    public function copy($fromLessonTaskId, $toLessonTaskId)
    {
        $fromLessonTask = $this->getCourseLessonService()->getLessonTask($fromLessonTaskId);
        $fromTask = $this->getTaskService()->getTask($fromLessonTask['taskId']);
        $fromRelations = $this->getTeachingAimActivityService()->findByActivityId($fromTask['activityId']);
        
        if (empty($fromRelations)) {
            return;
        }

        $toLessonTask = $this->getCourseLessonService()->getLessonTask($toLessonTaskId);
        $toTask = $this->getTaskService()->getTask($toLessonTask['taskId']);
        $toCourse = $this->getCourseService()->getCourse($toTask['courseId']);
        $toCourseSet = $this->getCourseSetService()->getCourseSet($toCourse['courseSetId']);

        $copyRelations = array();

        foreach ($fromRelations as $fromRelation) {
            $fromAim = $this->getTeachingAimService()->getByAimId($fromRelation['aimId']);
            $copyAim = $this->getTeachingAimService()->getByParentIdAndLessonId($fromAim['id'], $toLessonTask['lessonId']);
            if (empty($copyAim)) {
                continue;
            }

            $copyRelations[] = array(
                'aimId' => $copyAim['id'],
                'courseId' => $toTask['courseId'],
                'orgCode' => $toCourseSet['orgCode'],
                'activityId' => $toTask['activityId'],
                'teacherId' => $fromTask['createdUserId'],
                'termCode' => $toCourse['termCode'],
            );
        }

        $this->getTeachingAimActivityService()->batchCreate($copyRelations);
    }

    protected function getTaskService()
    {
        return $this->getServiceKernel()->createService('Task:TaskService');
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

    protected function getTeachingAimActivityService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Lesson:TeachingAimActivityService');
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
