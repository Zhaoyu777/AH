<?php

namespace CustomBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Command\BaseCommand;
use AppBundle\Common\ArrayToolkit;

class ZhktTestMigrateMainTeachersCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('zhkt-test:migrate-main-teachers')
            ->setDescription('主带老师数据迁移')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('开始迁移数据');
        $mainTeachers = $this->getCourseService()->findAllCourseMasterTeachers();
        $allCourseIds = ArrayToolkit::column($mainTeachers, 'courseId');
        $mainTeachers = ArrayToolkit::index($mainTeachers, 'courseId');

        $allMainTeachers = $this->getMemberService()->findAllMainTeachers();
        $courseIds = ArrayToolkit::column($allMainTeachers, 'courseId');

        $diffCourseIds = array_diff($allCourseIds, $courseIds);

        if (empty($diffCourseIds)) {
            $output->writeln('无数据需要迁移');
            return ;
        }

        $insertRecords = ArrayToolkit::parts($mainTeachers, $diffCourseIds);
        $this->getMemberService()->batchCreate($mainTeachers);

        $output->writeln('数据迁移完成');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }
}
