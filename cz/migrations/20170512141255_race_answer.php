<?php

use Phpmig\Migration\Migration;

class RaceAnswer extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_race_answer` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `createdUserId` int(11) NOT NULL,
                `createdTime` int(10) DEFAULT 0,
                `updatedTime` int(11) DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table activity_race_answer');
    }
}
