<?php

namespace Biz\Sms\Event;

use Biz\CloudPlatform\CloudAPIFactory;
use Biz\Sms\Service\SmsService;
use Biz\Sms\SmsProcessor\SmsProcessorFactory;
use Codeages\Biz\Framework\Event\Event;
use Codeages\Biz\Framework\Scheduler\Service\SchedulerService;
use Codeages\Biz\Framework\Service\Exception\ServiceException;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TaskEventSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'course.task.unpublish' => 'onTaskUnpublish',
            'course.task.publish' => 'onTaskPublish',
            'course.task.update' => 'onTaskUpdate',
            'course.task.delete' => 'onTaskDelete',
        );
    }

    public function onTaskUnpublish(Event $event)
    {
        $task = $event->getSubject();
        $this->deleteJob($task);
    }

    public function onTaskDelete(Event $event)
    {
        $task = $event->getSubject();
        $this->deleteJob($task);
    }

    public function onTaskUpdate(Event $event)
    {
        $task = $event->getSubject();
        if ($task['type'] == 'live') {
            $this->deleteJob($task);

            if ($task['status'] == 'published') {
                $this->registerJob($task);
            }
        }
    }

    public function onTaskPublish(Event $event)
    {
        $task = $event->getSubject();

        if ($task['type'] == 'live') {
            $this->registerJob($task);
            $smsType = 'sms_live_lesson_publish';
        } else {
            $smsType = 'sms_normal_lesson_publish';
        }

        if ($this->getSmsService()->isOpen($smsType)) {
            $processor = SmsProcessorFactory::create('task');
            $return = $processor->getUrls($task['id'], $smsType);
            $callbackUrls = $return['urls'];
            $count = ceil($return['count'] / 1000);
            try {
                $api = CloudAPIFactory::create('root');
                $result = $api->post('/sms/sendBatch', array('total' => $count, 'callbackUrls' => $callbackUrls));
            } catch (\Exception $e) {
                throw new ServiceException('发送失败！');
            }
        }
    }

    protected function registerJob($task)
    {
        $dayIsOpen = $this->getSmsService()->isOpen('sms_live_play_one_day');
        $hourIsOpen = $this->getSmsService()->isOpen('sms_live_play_one_hour');

        if ($dayIsOpen && $task['startTime'] >= (time() + 24 * 60 * 60)) {
            $startJob = array(
                'name' => 'SmsSendOneDayJob_task_'.$task['id'],
                'expression' => $task['startTime'] - 24 * 60 * 60,
                'class' => 'Biz\Sms\Job\SmsSendOneDayJob',
                'args' => array(
                    'targetType' => 'task',
                    'targetId' => $task['id'],
                ),
            );
            $this->getSchedulerService()->register($startJob);
        }

        if ($hourIsOpen && $task['startTime'] >= (time() + 60 * 60)) {
            $startJob = array(
                'name' => 'SmsSendOneHourJob_task_'.$task['id'],
                'expression' => $task['startTime'] - 60 * 60,
                'class' => 'Biz\Sms\Job\SmsSendOneHourJob',
                'args' => array(
                    'targetType' => 'task',
                    'targetId' => $task['id'],
                ),
            );
            $this->getSchedulerService()->register($startJob);
        }
    }

    /**
     * @return SchedulerService
     */
    private function getSchedulerService()
    {
        return $this->getBiz()->service('Scheduler:SchedulerService');
    }

    private function deleteJob($task)
    {
        $this->deleteByJobName('SmsSendOneDayJob_task_'.$task['id']);
        $this->deleteByJobName('SmsSendOneHourJob_task_'.$task['id']);
    }

    private function deleteByJobName($jobName)
    {
        $jobs = $this->getSchedulerService()->searchJobs(array('name' => $jobName), array(), 0, PHP_INT_MAX);

        foreach ($jobs as $job) {
            $this->getSchedulerService()->deleteJob($job['id']);
        }
    }

    /**
     * @return SmsService
     */
    protected function getSmsService()
    {
        return $this->getBiz()->service('Sms:SmsService');
    }
}
