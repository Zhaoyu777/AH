<?php

namespace CustomBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Command\BaseCommand;
use AppBundle\Common\ArrayToolkit;

class ZhktExecJobCommand extends BaseCommand
{
    protected $basicParams = array();

    protected function configure()
    {
        $this
            ->setName('zhkt-test:exec-job')
            ->setDescription('智慧课堂测试专用：执行定时任务')
            ->addArgument('name', InputArgument::REQUIRED, '定时任务名称')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>开始执行该定时任务</info>');

        $name = $input->getArgument('name');
        try {
            $currentTime = time();
            $jobName = $this->getSchedulerJobs($name);
            $connection = $this->getBiz()['db'];
            $connection->exec("UPDATE `biz_scheduler_job` SET next_fire_time = {$currentTime} WHERE name = '{$jobName}'");
            $jobCommand = $this->getBiz()['root_directory'].'app/console util:scheduler';
            shell_exec($jobCommand);
            // $jobConfig = $this->getJobConfigByName($name);
            // $jobClass = $this->initClass($jobConfig);

            // if (empty($jobConfig['method'])) {
            //     throw new \Exception("配置项缺少需要执行的方法名");
            // }

            // $func = $jobConfig['method'];
            // if (isset($jobConfig['methodParams'])) {
            //     $jobClass->$func($jobConfig['methodParams']);
            // } else {
            //     $jobClass->$func();
            // }
        } catch (\Exception $e) {
            $output->writeln('<info>执行失败</info>');
            $output->writeln("<info>原因：{$e->getMessage()}</info>");
        }

        $output->writeln('<info>执行完毕</info>');
    }

    protected function getSchedulerJobs($name)
    {
        $jobs = array(
            'signin-warning' => 'SignInWarningJob',
            'task-warning' => 'CourseTaskCompleWarningjob',
            'teaching-aim-warning' => 'TeachingAimWarningJob',
        );

        if (!array_key_exists($name, $jobs)) {
            throw new \Exception("该定时任务名称不存在");
        }

        return $jobs[$name];
    }

    protected function getJobConfigByName($name)
    {
        $jobs = array(
            'course-student' => array(
                'class' => "CustomBundle\\Biz\\Statistics\\Analyze\\Analyzer\\StudentCourseAnalyzer",
                'biz' => true,
                'params' => $this->fetchNeedParams(array('courseIds')),
                'method' => 'excute',
            ),
            'signin-warning' => array(
                'class' => "CustomBundle\\Biz\\SignIn\\Job\\SignInWarningJob",
                'params' => array(),
                'method' => 'execute',
                'methodParams' => array(),
            ),
            'teacher-lesson' => array(
                'class' => "CustomBundle\\Biz\\Statistics\\Statistics\\Statistics",
                'biz' => false,
                'params' => $this->fetchNeedParams(array('lessonIds', 'userIds')),
                'method' => 'statistics',
            ),
            'task-warning' => array(
                'class' => "CustomBundle\\Biz\\Course\\Job\\CourseTaskCompleWarningjob",
                'params' => array(),
                'method' => 'execute',
                'methodParams' => array(),
            ),
            'teaching-aim-warning' => array(
                'class' => "CustomBundle\\Biz\\Lesson\\Job\\TeachingAimWarningJob",
                'params' => array(),
                'method' => 'execute',
                'methodParams' => array()
            ),
        );

        if (!array_key_exists($name, $jobs)) {
            throw new \Exception("该定时任务名称不存在");
        }

        return $jobs[$name];
    }

    protected function initClass($jobConfig)
    {
        if (isset($jobConfig['biz']) && $jobConfig['biz']) {
            $biz = $this->getBiz();
            $class = new $jobConfig['class']($biz, $jobConfig['params']);
        } else {
            $class = new $jobConfig['class']($jobConfig['params']);
        }

        return $class;
    }

    protected function fetchNeedParams($names)
    {
        if (empty($names)) {
            return array();
        }

        $basicParams = $this->fetchBasicParams();

        $results = array();
        foreach ($names as $name) {
            if (!array_key_exists($name, $basicParams)) {
                throw new \Exception("该类的初始化参数名设置错误");
            }

            $results = array_merge($results, array($name => $basicParams[$name]));
        }

        return $results;
    }

    protected function fetchBasicParams()
    {
        if (empty($this->basicParams)) {
            $courseIds = $this->findWillAnalyzeCourseIds();
            $lessonIds = $this->findWillAnalyzeLessonIds();
            $userIds = $this->findWillAnalyzeUserIds();

            $this->basicParams = array(
                'courseIds' => $courseIds,
                'lessonIds' => $lessonIds,
                'userIds' => $userIds,
            );
        }

        return $this->basicParams;
    }

    protected function findWillAnalyzeCourseIds()
    {
        $courseIds = array();

        $courseLessons = $this->getCourseLessonService()->findTeachedCourseLessonByTime(0, time());

        $courseIds = ArrayToolkit::column($courseLessons, 'courseId');

        return array_unique($courseIds);
    }

    protected function findWillAnalyzeLessonIds()
    {
        $lessons = $this->getCourseLessonService()->findTeachedCourseLessonByTime(0, time());
        $lessonIds = ArrayToolkit::column($lessons, 'id');

        return array_unique($lessonIds);
    }

    protected function findWillAnalyzeUserIds()
    {
        $users = $this->getLogService()->findLogByTime(0, time());
        $userIds = ArrayToolkit::column($users, 'userId');

        return array_unique($userIds);
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getLogService()
    {
        return $this->createService('CustomBundle:System:LogService');
    }
}
