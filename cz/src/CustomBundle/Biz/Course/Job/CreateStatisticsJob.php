<?php

namespace CustomBundle\Biz\Course\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use CustomBundle\Biz\Statistics\Statistics\Statistics;
use Biz\Crontab\Service\Job;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Codeages\Biz\Framework\Scheduler\Service\SchedulerService;

class CreateStatisticsJob extends AbstractJob
{
    public function execute()
    {
        try {
            $lessonIds = $this->findStatisticsLessonIds();
            $userIds = $this->findStatisticsUserIds();

            if (!empty($lessonIds)) {
                $type = 'lesson';
                $allSteps = count($lessonIds);
            } else {
                $type = 'teacher';
                $allSteps = count($userIds);
            }

            if (empty($userIds) && empty($lessonIds)) {
                return;
            }

            $currentTime = time();
            $job = array(
                'name' => 'CycleAnalyzeStatisticsJob',
                'expression' => time() + 100, 
                'class' => 'CustomBundle\Biz\Course\Job\CycleAnalyzeStatisticsJob',
                'args' => array(
                    'lessonIds' => $lessonIds,
                    'userIds' => $userIds,
                    'type' => $type,
                    'allSteps' => $allSteps,
                    'currentStep' => 1,
                ),
                'priority' => '100',
                'misfire_threshold' => '3000',
                'misfire_policy' => 'missed',
            );

            $this->getSchedulerService()->register($job);
        } catch (\Exception $e) {
        }
    }

    protected function findStatisticsUserIds()
    {
        $endTime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $startTime = $endTime-86400;
        $users = $this->getLogService()->findLogByTime($startTime, $endTime);

        $userIds = ArrayToolkit::column($users, 'userId');
        $userIds = array_unique($userIds);
        $userIds = array_chunk($userIds, 100, false);

        return $userIds;
    }

    protected function findStatisticsLessonIds()
    {
        $endTime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $startTime = $endTime-86400;
        $lessons = $this->getCourseLessonService()->findTeachedCourseLessonByTime($startTime, $endTime);

        $lessonIds = ArrayToolkit::column($lessons, 'id');
        $lessonIds = array_unique($lessonIds);
        $lessonIds = array_chunk($lessonIds, 200, false);

        return $lessonIds;
    }

    protected function getLogService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:System:LogService');
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }

    /*protected function getCrontabService()
    {
        return $this->getServiceKernel()->createService('Crontab:CrontabService');
    }*/

    protected function getSchedulerService()
    {
        return $this->getServiceKernel()->createService('Scheduler:SchedulerService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
