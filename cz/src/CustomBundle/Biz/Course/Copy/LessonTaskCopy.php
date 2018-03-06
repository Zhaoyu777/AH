<?php

namespace CustomBundle\Biz\Course\Copy;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LessonTaskCopy extends AbstractCopy
{
    private $taskCopy;

    private $taskAimsConnectCopy;

    public function __construct()
    {
        $this->taskCopy = new TaskCopy();

        $this->taskAimsConnectCopy = new TaskAimsConnectCopy();
    }

    public function copy($fromLessonId, $toLessonId)
    {
        $toLesson = $this->getCourseLessonService()->getCourseLesson($toLessonId);

        $fromLessonTasks = $this->getCourseLessonService()->findLessonTasksByLessonId($fromLessonId);

        $lessonTask = array();
        foreach ($fromLessonTasks as $fromLessonTask) {
            $lessonTaskFiled = $this->getCourseLessonService()->getLessonTask($fromLessonTask['id']);

            $task = $this->taskCopy->copy($fromLessonTask['taskId'], $toLessonId);
            $lessonTaskFiled['courseId'] = $toLesson['courseId'];
            $lessonTaskFiled['lessonId'] = $toLesson['id'];
            $lessonTaskFiled['taskId'] = $task['id'];
            $lessonTaskFiled['copy'] = 'copy';
            $toLessonTask = $this->getCourseLessonService()->createLessonTask($lessonTaskFiled);
            $lessonTask[] = $toLessonTask;
            $this->taskAimsConnectCopy->copy($fromLessonTask['id'], $toLessonTask['id']);
        }

        return $lessonTask;
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
