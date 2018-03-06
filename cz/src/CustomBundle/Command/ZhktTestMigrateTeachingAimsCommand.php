<?php

namespace CustomBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Command\BaseCommand;
use AppBundle\Common\ArrayToolkit;

class ZhktTestMigrateTeachingAimsCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('zhkt-test:migrate-teaching-aims')
            ->setDescription('课次教学目标数据迁移')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('开始迁移数据');

        $courseLessons = $this->getCourseLessonService()->findAllLessons();
        $teachingAims = $this->getTeachingAimService()->findAllAims();
        $teachingAims = ArrayToolkit::group($teachingAims, 'lessonId');

        if (empty($courseLessons)) {
            $output->writeln('无数据需要迁移');
            return;
        }

        $datas = array();
        foreach ($courseLessons as $lesson) {
            if (!empty($teachingAims[$lesson['id']])) {
                continue;
            }

            if (!empty($lesson['abilityAim'])) {
                $datas[] = array(
                    'courseId' => $lesson['courseId'],
                    'lessonId' => $lesson['id'],
                    'content' => strip_tags($lesson['abilityAim']),
                    'number' => 1,
                    'type' => 'abilityAim',
                );
            }
            if (!empty($lesson['aknowledgeAim'])) {
                $datas[] = array(
                    'courseId' => $lesson['courseId'],
                    'lessonId' => $lesson['id'],
                    'content' => strip_tags($lesson['aknowledgeAim']),
                    'number' => 1,
                    'type' => 'knowledgeAim',
                );
            }
            if (!empty($lesson['qualityAim'])) {
                $datas[] = array(
                    'courseId' => $lesson['courseId'],
                    'lessonId' => $lesson['id'],
                    'content' => strip_tags($lesson['qualityAim']),
                    'number' => 1,
                    'type' => 'qualityAim',
                );
            }
        }

        if (empty($datas)) {
            $output->writeln('无数据需要迁移');
            return ;
        }

        $this->getTeachingAimService()->batchCreate($datas);

        $output->writeln('数据迁移完成');
    }
    
    protected function getCourseLessonService()
    {
        return $this->createService("CustomBundle:Course:CourseLessonService");
    }

    protected function getTeachingAimService()
    {
        return $this->createService("CustomBundle:Lesson:TeachingAimService");
    }
}
