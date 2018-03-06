<?php

use Phpmig\Migration\Migration;

class JobsChange extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        $data = $biz['db']->fetchAssoc("SELECT * FROM `crontab_job` WHERE name = 'AnalyzeCourseStudentsStatisticsJob'");

        if (empty($data)) {
            return ;
        }

        $className = addslashes('CustomBundle\Biz\Course\Job\StartAnalyzeCourseStudentsStatisticsJob');
        $biz['db']->exec("
            UPDATE `crontab_job` SET name = 'StartAnalyzeCourseStudentsStatisticsJob', jobClass = '{$className}' WHERE id = {$data['id']}
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
