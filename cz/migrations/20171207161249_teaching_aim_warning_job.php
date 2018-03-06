<?php

use Phpmig\Migration\Migration;

class TeachingAimWarningJob extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        $result = $biz['db']->fetchAssoc("SELECT * FROM `crontab_job` WHERE name = 'TeachingAimWarningJob'");

        if (!empty($result)) {
            return;
        }

        $className = addslashes('CustomBundle\Biz\Lesson\Job\TeachingAimWarningJob');
        $biz['db']->exec("
            INSERT INTO `crontab_job` (`name`, `cycle`, `cycleTime`, `jobClass`, `jobParams`, `targetType`, `targetId`, `executing`, `nextExcutedTime`, `latestExecutedTime`, `creatorId`, `createdTime`, `enabled`) VALUES('TeachingAimWarningJob', 'everymonth', '21:30', '{$className}', '\"{}\"', '', 0, 0, UNIX_TIMESTAMP(adddate(date(sysdate()), INTERVAL +1 MONTH)), 0, 2, UNIX_TIMESTAMP(sysdate()), 1);
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
