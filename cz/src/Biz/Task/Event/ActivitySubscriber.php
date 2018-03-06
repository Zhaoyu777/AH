<?php

namespace Biz\Task\Event;

use Biz\Task\Service\TaskService;
use Codeages\Biz\Framework\Event\Event;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivitySubscriber extends EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'activity.start' => 'onActivityStart',
            'activity.doing' => 'onActivityDoing',
        );
    }

    public function onActivityStart(Event $event)
    {
        $user = $this->getBiz()->offsetGet('user');
        $task = $event->getArgument('task');
        $this->getTaskService()->startTask($task['id']);
        $this->updateLastLearnTime($task);
    }

    public function updateLastLearnTime($task)
    {
        $user = $this->getBiz()->offsetGet('user');
        $courseMember = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);
        $this->dispatch('task.show', $courseMember);
    }

    public function onActivityDoing(Event $event)
    {
        $task = $event->getArgument('task');
        $lastTime = $event->getArgument('lastTime');
        $time = time() - $lastTime;
        if ($time >= TaskService::LEARN_TIME_STEP) {
            $time = TaskService::LEARN_TIME_STEP;
        }

        if (empty($task)) {
            return;
        }
        if ($time > 0) {
            $this->getTaskService()->doTask($task['id'], $time);
        }
        $this->updateLastLearnTime($task);

        if ($this->getTaskService()->isFinished($task['id'])) {
            $this->getTaskService()->finishTaskResult($task['id']);
        }
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->getBiz()->service('Task:TaskService');
    }

    protected function getTaskResultService()
    {
        return $this->getBiz()->service('Task:TaskResultService');
    }

    protected function getCourseMemberService()
    {
        return $this->getBiz()->service('Course:MemberService');
    }

    protected function dispatch($eventName, $subject)
    {
        $event = new Event($subject);

        return $this->getBiz()->offsetGet('dispatcher')->dispatch($eventName, $event);
    }
}
