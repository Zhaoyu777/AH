<?php

use Phpmig\Migration\Migration;

class AddSchedulerJobCourseStudentStatistics extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        $data = $biz['db']->fetchAssoc("SELECT * FROM `biz_scheduler_job` WHERE name = 'AnalyzeCourseStudentsStatisticsJob'");

        if (empty($data)) {
            return ;
        }

        $className = addslashes('CustomBundle\Biz\Course\Job\StartAnalyzeCourseStudentsStatisticsJob');
        $biz['db']->exec("
            UPDATE `biz_scheduler_job` SET name = 'StartAnalyzeCourseStudentsStatisticsJob', class = '{$className}' WHERE id = {$data['id']}
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
