<?php

namespace CustomBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Command\BaseCommand;
use AppBundle\Common\ArrayToolkit;

class UpdateStudentReportDataCommand extends BaseCommand
{
    protected $basicParams = array();

    protected function configure()
    {
        $this
            ->setName('student-report:data-update')
            ->setDescription('学生课堂报告表老数据更新')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>开始执行老数据更新</info>');

        $conditions = array(
            'status' => 'teached',
        );

        $lessonCount = $this->getCourseLessonService()->countCourseLesson($conditions);
        $output->writeln('<info>总共已结束'.$lessonCount.'个课次</info>');
        $perLessonCount = 100;
        $times = ceil($lessonCount / $perLessonCount);
        $output->writeln('<info>总共需执行'.$times.'次更新，每次计算'.$perLessonCount.'个课次</info>');
        $allReports = array();
        for ($i=0; $i < $times; $i++) {
            $lessons = $this->getCourseLessonService()->searchCourseLesson(
                $conditions,
                array(),
                $i * $perLessonCount,
                $perLessonCount
            );

            foreach ($lessons as $lesson) {
                $memberConditions = array(
                    'role' => 'student',
                    'courseId' => $lesson['courseId'],
                    'startTimeLessThan' => $lesson['endTime'],
                );
                $students = $this->getCourseMemberService()->searchMembers(
                    $memberConditions,
                    array(),
                    0,
                    PHP_INT_MAX
                );
                $reports = $this->getReportService()->findReportBylessonId($lesson['id']);
                $reports = ArrayToolkit::index($reports, 'userId');
                foreach ($students as &$student) {
                    if (!empty($reports[$student['userId']])) {
                        $report = $reports[$student['userId']];
                        $student = array(
                            'courseId' => $report['courseId'],
                            'lessonId' => $report['lessonId'],
                            'userId' => $report['userId'],
                            'taskInCompletionRate' => $report['taskInCompletionRate'],
                            'taskBeforCompletionRate' => $report['taskBeforCompletionRate'],
                            'exerciseNumber' => $report['exerciseNumber'],
                        );
                    } else {
                        $student = array(
                            'courseId' => $lesson['courseId'],
                            'lessonId' => $lesson['id'],
                            'userId' => $student['userId'],
                            'taskInCompletionRate' => '0.0',
                            'taskBeforCompletionRate' => '0.0',
                            'exerciseNumber' => '0.0',
                        );
                    }
                }
                $allReports = array_merge($allReports, $students);
            }

            $output->writeln('<info>计算第'.($i + 1).'次结束</info>');
        }
        $output->writeln('<info>开始写入数据</info>');
        $this->getReportService()->updateReportTable($allReports);

        $output->writeln('<info>执行完毕</info>');
    }

    protected function getReportService()
    {
        return $this->createService('CustomBundle:Report:StudentLessonReportService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
