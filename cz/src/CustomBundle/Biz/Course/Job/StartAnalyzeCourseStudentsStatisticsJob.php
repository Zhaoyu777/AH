<?php

namespace CustomBundle\Biz\Course\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Statistics\Analyze\DatasAnalyze;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Codeages\Biz\Framework\Scheduler\Service\SchedulerService;

class StartAnalyzeCourseStudentsStatisticsJob extends AbstractJob
{
    public function execute()
    {
        $analyzeCourseIds = $this->findWillAnalyzeCourseIds();
        $courseIds = array_chunk($analyzeCourseIds, 100, false);
        $this->log('---开始分析课程学生学习数据，总共需要分析'.count($analyzeCourseIds).'门课程,分成'.count($courseIds).'组分析，每组100个---');

        $this->getSchedulerService()->register(array(
            'name' => 'CycleAnalyzeCourseStudentsStatisticsJob',
            'expression' => time() + 60,
            'class' => 'CustomBundle\\Biz\\Course\\Job\\CycleAnalyzeCourseStudentsStatisticsJob',
            'priority' => '100',
            'misfire_threshold' => '3000',
            'misfire_policy' => 'missed',
        ));
    }

    protected function findWillAnalyzeCourseIds()
    {
        $courseIds = array();

        $time = date('Y-m-d');
        $toTime = strtotime($time);
        $fromTime = $toTime - 86400;

        $courseLessons = $this->getCourseLessonService()->findTeachedCourseLessonByTime(0, time());

        $courseIds = ArrayToolkit::column($courseLessons, 'courseId');

        return array_unique($courseIds);
    }

    protected function log($message)
    {
        $logger = new Logger('Logger');
        $biz = $this->getServiceKernel()->getBiz();
        $logger->pushHandler(new StreamHandler($biz['log_directory'].'/job.log', Logger::DEBUG));
        $logger->info($message);
    }

    protected function beginTransaction()
    {
        $biz = $this->getBiz();
        $biz['db']->beginTransaction();
    }

    protected function commit()
    {
        $biz = $this->getBiz();
        $biz['db']->commit();
    }

    protected function rollback()
    {
        $biz = $this->getBiz();
        $biz['db']->rollback();
    }

    protected function getBiz()
    {
        return $this->getServiceKernel()->getBiz();
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
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
