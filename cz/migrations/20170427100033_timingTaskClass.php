<?php

use Phpmig\Migration\Migration;

class TimingTaskClass extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec("
            INSERT INTO `crontab_job` (`name`, `cycle`, `cycleTime`, `jobClass`, `jobParams`, `targetType`, `targetId`, `executing`, `nextExcutedTime`, `latestExecutedTime`, `creatorId`, `createdTime`, `enabled`) VALUES('EndLessonsJob', 'everyday', '23:59', 'Custom\\Biz\\Course\\Job\\EndLessonsJob', '\"{}\"', '', 0, 0, UNIX_TIMESTAMP(adddate(date(sysdate()),1)), 0, 2, UNIX_TIMESTAMP(sysdate()), 1);
        ");
        $connection->exec('UPDATE `crontab_job` SET jobClass = \'Custom\\\\Biz\\\\Course\\\\Job\\\\EndLessonsJob\' WHERE name = \'EndLessonsJob\';');
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
