<?php

use Phpmig\Migration\Migration;

class ZhktSchedulerJob extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $jobFireds = $connection->fetchAll("select * from biz_scheduler_job_fired where status in ('executing', 'acquired');");
        foreach ($jobFireds as $jobFired) {
            $job = $connection->fetchAssoc("select * from biz_scheduler_job where id={$jobFired['job_id']}");
            $jobDetail = '';
            if (!empty($job)) {
                $jobDetail = json_encode($job);
            }
            $connection->exec("update biz_scheduler_job_fired set job_detail='{$jobDetail}' where id={$jobFired['id']}");
        }
        $this->createEndLessonsJob($connection);
        $this->createStatisticsJob($connection);
        $this->createAnalyzeCourseStudentsStatisticsJob($connection);
    }

    private function createEndLessonsJob($connection)
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
              'EndLessonsJob',
              '59 23 * * *',
              'CustomBundle\\\\Biz\\\\Course\\\\Job\\\\EndLessonsJob',
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

    private function createStatisticsJob($connection)
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
              'CreateStatisticsJob',
              '10 0 * * *',
              'CustomBundle\\\\Biz\\\\Course\\\\Job\\\\CreateStatisticsJob',
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

    private function createAnalyzeCourseStudentsStatisticsJob($connection)
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
              'AnalyzeCourseStudentsStatisticsJob',
              '40 0 * * *',
              'CustomBundle\\\\Biz\\\\Course\\\\Job\\\\AnalyzeCourseStudentsStatisticsJob',
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

        $connection->exec("delete from biz_scheduler_job where name = 'EndLessonsJob'");
        $connection->exec("delete from biz_scheduler_job where name = 'CreateStatisticsJob'");
        $connection->exec("delete from biz_scheduler_job where name = 'AnalyzeCourseStudentsStatisticsJob'");
    }
}
