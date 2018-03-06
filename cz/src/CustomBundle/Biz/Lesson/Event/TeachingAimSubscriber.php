<?php

namespace CustomBundle\Biz\Lesson\Event;

use Codeages\Biz\Framework\Event\Event;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AppBundle\Common\ArrayToolkit;

class TeachingAimSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'lesson.task.create'  => 'onLessonTaskCreate',
            'course.lesson.update' => 'onLessonUpdate',
            'course.task.delete' => 'onCourseTaskDelete',
            'lesson.aims.delete' => 'onLessonAimsDelete',
        );
    }

    public function onLessonAimsDelete(Event $event)
    {
        $delteAims = $event->getSubject();

        $this->getTeachingAimActivityService()->deleteByAimIds($delteAims['aimIds']);
    }

    public function onLessonTaskCreate(Event $event)
    {
        $lessonTask = $event->getSubject();
        $fields = $event->getArgument('fields');

        if (!empty($fields['aimIds'])) {
            $task = $this->getTaskService()->getTask($lessonTask['taskId']);

            $this->getTeachingAimActivityService()->connectAims($task['activityId'], $lessonTask['lessonId'], $fields['aimIds']);
        }
    }

    public function onLessonUpdate(Event $event)
    {
        $lesson = $event->getSubject();
        $fields = $event->getArgument('fields');

        if (!isset($fields['abilityAims']) && !isset($fields['knowledgeAims']) && !isset($fields['qualityAims'])) {
            return;
        }

        $fields['lessonId'] = $lesson['id'];
        $fields['courseId'] = $lesson['courseId'];

        $this->getTeachingAimService()->modifyAims($lesson['id'], $fields);
    }

    public function onCourseTaskDelete(Event $event)
    {
        $task = $event->getSubject();

        $this->getTeachingAimActivityService()->deleteByActivityId($task['activityId']);
    }

    protected function getTaskService()
    {
        return $this->getBiz()->service('Task:TaskService');
    }

    protected function getTeachingAimService()
    {
        return $this->getBiz()->service('CustomBundle:Lesson:TeachingAimService');
    }

    protected function getActivityService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:ActivityService');
    }

    protected function getTeachingAimActivityService()
    {
        return $this->getBiz()->service('CustomBundle:Lesson:TeachingAimActivityService');
    }

    protected function getCourseLessonService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseLessonService');
    }
}
