<?php

namespace CustomBundle\Biz\Lesson\Event;

use Codeages\Biz\Framework\Event\Event;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LessonRecordSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'course.lesson.start'  => 'onLessonStart',
            'course.lesson.cancel' => 'onLessonCancel',
            'course.lesson.end'    => 'onLessonFinish',
        );
    }

    public function onLessonStart(Event $event)
    {
        $courseLesson = $event->getSubject();
        $userId = $event->getArgument('userId');
        $course = $this->getCourseService()->getCourse($courseLesson['courseId']);
        $fields = array(
            'courseSetId' => $course['courseSetId'],
            'courseId'    => $courseLesson['courseId'],
            'lessonId'    => $courseLesson['id'],
            'teacherId'   => $userId,
        );
        $lessonBeforeTaskCount = $this->getCourseLessonService()->countLessonTask(array(
            'lessonId' => $courseLesson['id'],
            'stage' => 'before',
        ));
        if ($lessonBeforeTaskCount == 0) {
            $task = $this->getTaskService()->getFirstInClassTaskByLessonId($courseLesson['id']);
            $fields['taskId'] = $task['id'];
        }

        $this->getRecordService()->create($fields);
    }

    public function onLessonCancel(Event $event)
    {
        $courseLesson = $event->getSubject();

        $this->getRecordService()->deleteRecordsByLessonId($courseLesson['id']);
    }

    public function onLessonFinish(Event $event)
    {
        $courseLesson = $event->getSubject();
        $this->getReportService()->createLessonReport($courseLesson['id']);

        $this->getRecordService()->deleteRecordsByLessonId($courseLesson['id']);
    }

    protected function getRecordService()
    {
        return $this->getBiz()->service('CustomBundle:Lesson:RecordService');
    }

    protected function getCourseLessonService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    protected function getReportService()
    {
        return $this->getBiz()->service('CustomBundle:Report:StudentLessonReportService');
    }

    protected function getTaskService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }
}
