<?php

use Phpmig\Migration\Migration;

class CrontabJobAddStudentCourseStatistics extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec("
            INSERT INTO `crontab_job` (`name`, `cycle`, `cycleTime`, `jobClass`, `jobParams`, `targetType`, `targetId`, `executing`, `nextExcutedTime`, `latestExecutedTime`, `creatorId`, `createdTime`, `enabled`) VALUES('AnalyzeCourseStudentsStatisticsJob', 'everyday', '00:30', 'CustomBundle\\Biz\\Course\\Job\\AnalyzeCourseStudentsStatisticsJob', '\"{}\"', '', 0, 0, UNIX_TIMESTAMP(adddate(date(sysdate()),1)), 0, 2, UNIX_TIMESTAMP(sysdate()), 1);
        ");
        $connection->exec('UPDATE `crontab_job` SET jobClass = \'CustomBundle\\\\Biz\\\\Course\\\\Job\\\\AnalyzeCourseStudentsStatisticsJob\' WHERE name = \'AnalyzeCourseStudentsStatisticsJob\';');
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
