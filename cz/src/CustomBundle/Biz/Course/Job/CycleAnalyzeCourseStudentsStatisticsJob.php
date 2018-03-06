<?php

namespace CustomBundle\Biz\Course\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Statistics\Analyze\Analyzer\StudentCourseAnalyzer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Codeages\Biz\Framework\Scheduler\Service\SchedulerService;

class CycleAnalyzeCourseStudentsStatisticsJob extends AbstractJob
{
    public function execute()
    {
        $params = $this->args;
        $step = $params['currentStep'];
        $courseIds = $params['courseIds'][--$step];

        $this->log('---现在分析第'.$params['currentStep'].'/'.$params['allSteps'].'组courseIds---');
        $time = time();
        $biz = $this->getServiceKernel()->getBiz();
        $analyzer = new StudentCourseAnalyzer($biz, array(
            'courseIds' => $courseIds
        ));
        try {
            $analyzer->excute();
        } catch (\Exception $e) {
            $this->log('---课程id：'.$e->getMessage().'分析失败---', true);
        }

        $this->log('---第'.$params['currentStep'].'/'.$params['allSteps'].'组courseIds分析完毕，耗时'.(time() - $time).'秒---');

        if ($params['currentStep'] < $params['allSteps']) {
            $job = array(
                'name' => 'CycleAnalyzeCourseStudentsStatisticsJob',
                'expression' => time() + 60,
                'class' => 'CustomBundle\Biz\Course\Job\CycleAnalyzeCourseStudentsStatisticsJob',
                'args' => array(
                    'courseIds' => $params['courseIds'],
                    'allSteps' => $params['allSteps'],
                    'currentStep' => ++$params['currentStep'],
                ),
                'priority' => '100',
                'misfire_threshold' => '3000',
                'misfire_policy' => 'missed',
            );

            $this->getSchedulerService()->register($job);
        }

        if ($params['currentStep'] == $params['allSteps']) {
            $this->log('---分析结束---');
        }
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
