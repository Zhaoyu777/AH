<?php

namespace CustomBundle\Biz\Course\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Statistics\Statistics\Statistics;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Codeages\Biz\Framework\Scheduler\Service\SchedulerService;

class CycleAnalyzeStatisticsJob extends AbstractJob
{
    public function execute()
    {
        $params = $this->args;
        $params = $this->statistics($params);

        if ($params['currentStep'] < $params['allSteps']) {
            $currentTime = time();
            $job = array(
                'name' => 'CycleAnalyzeStatisticsJob',
                'expression' => time() + 10, 
                'class' => 'CustomBundle\Biz\Course\Job\CycleAnalyzeStatisticsJob',
                'args' => array(
                    'lessonIds' => $params['lessonIds'],
                    'userIds' => $params['userIds'],
                    'type' => $params['type'],
                    'allSteps' => $params['allSteps'],
                    'currentStep' => 1,
                ),
                'priority' => '100',
                'misfire_threshold' => '3000',
                'misfire_policy' => 'missed',
            );

            $this->getSchedulerService()->register($job);
        }
    }

    protected function statistics($params)
    {
        if ($params['type'] == 'lesson') {
            $params = $this->lessonStatistics($params);
        } else {
            $params = $this->teacherStatistics($params);
        }

        if ($params['type'] == 'teacher' && $params['currentStep'] == $params['allSteps']) {
            $this->log('---分析结束---');
        }

        return $params;
    }

    protected function lessonStatistics($params)
    {
        $time = time();
        $step = $params['currentStep'];
        $statistics = new Statistics();

        $this->log('---现在分析第'.$params['currentStep'].'/'.$params['allSteps'].'组lessonIds---');

        $lessonIds = $params['lessonIds'][--$step];

        try {
            $statistics->courseStatistics($lessonIds);
        } catch (\Exception $e) {
            $this->log('---课次id：'.$e->getMessage().'分析失败---', true);
        }

        $this->log('---第'.$params['currentStep'].'/'.$params['allSteps'].'组lessonIds分析完毕，耗时'.(time() - $time).'秒---');

        if ($params['currentStep'] >= $params['allSteps']) {
            $params['type'] = 'teacher';
            $params['currentStep'] = 0;
            $params['allSteps'] = count($params['userIds']);
        }

        return $params;
    }

    protected function teacherStatistics($params)
    {
        $time = time();
        $step = $params['currentStep'];
        $statistics = new Statistics();

        $this->log('---现在分析第'.$params['currentStep'].'/'.$params['allSteps'].'组teacherIds---');
        $userIds = $params['userIds'][--$step];

        try {
            $statistics->teacherStatistics($userIds);
        } catch (\Exception $e) {
            $this->log('---教师id：'.$e->getMessage().'分析失败---', true);
        }

        $this->log('---第'.$params['currentStep'].'/'.$params['allSteps'].'组teacherIds分析完毕，耗时'.(time() - $time).'秒---');

        return $params;
    }

    protected function log($message, $isError = false)
    {
        $logger = new Logger('Logger');
        $biz = $this->getServiceKernel()->getBiz();
        $logger->pushHandler(new StreamHandler($biz['log_directory'].'/job.log', Logger::DEBUG));

        if ($isError) {
            $logger->error($message);
        } else {
            $logger->info($message);
        }
    }

    protected function getSchedulerService()
    {
        return $this->getServiceKernel()->createService('Scheduler:SchedulerService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
