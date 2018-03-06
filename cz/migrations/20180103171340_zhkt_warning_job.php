<?php

use Phpmig\Migration\Migration;

class ZhktWarningJob extends Migration
{

    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

       $this->createJob($connection);
    }

    private function createJob($connection)
    {
        $currentTime = time();
        $connection->exec("INSERT INTO `biz_scheduler_job` (
          `name`,
          `expression`,
          `class`,
          `args`,
          `priority`,
          `pre_fire_time`,
          `next_fire_time`,
          `misfire_threshold`,
          `misfire_policy`,
          `enabled`,
          `creator_id`,
          `updated_time`,
          `created_time`
    ) VALUES (
          'TeachingAimWarningJob',
          '* 21 * * 7',
          'CustomBundle\\\\Biz\\\\Lesson\\\\Job\\\\TeachingAimWarningJob',
          '',
          '100',
          '0',
          '{$currentTime}',
          '3000',
          'missed',
          '1',
          '0',
          '{$currentTime}',
          '{$currentTime}'
    ),(
          'CourseTaskCompleWarningjob',
          '* 21 * * 7',
          'CustomBundle\\\\Biz\\\\Course\\\\Job\\\\CourseTaskCompleWarningjob',
          '',
          '100',
          '0',
          '{$currentTime}',
          '3000',
          'missed',
          '1',
          '0',
          '{$currentTime}',
          '{$currentTime}'
    ),(
          'SignInWarningJob',
          '* 21 * * 7',
          'CustomBundle\\\\Biz\\\\SignIn\\\\Job\\\\SignInWarningJob',
          '',
          '100',
          '0',
          '{$currentTime}',
          '3000',
          'missed',
          '1',
          '0',
          '{$currentTime}',
          '{$currentTime}'
    ),(
          'StartAnalyzeCourseStudentsStatisticsJob',
          '40 0 * * *',
          'CustomBundle\\\\Biz\\\\Course\\\\Job\\\\StartAnalyzeCourseStudentsStatisticsJob',
          '',
          '100',
          '0',
          '{$currentTime}',
          '3000',
          'missed',
          '1',
          '0',
          '{$currentTime}',
          '{$currentTime}'
    )");
    }
    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("delete from biz_scheduler_job where name = 'TeachingAimWarningJob'");
        $connection->exec("delete from biz_scheduler_job where name = 'CourseTaskCompleWarningjob'");
        $connection->exec("delete from biz_scheduler_job where name = 'SignInWarningJob'");
        $connection->exec("delete from biz_scheduler_job where name = 'StartAnalyzeCourseStudentsStatisticsJob'");
    }
}
