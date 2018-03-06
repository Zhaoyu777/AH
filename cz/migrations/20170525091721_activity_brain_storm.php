<?php

use Phpmig\Migration\Migration;

class ActivityBrainStorm extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_brain_storm` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `groupWay` enum('fixed','random') NOT NULL DEFAULT 'fixed' COMMENT '分组方式',
              `groupNumber` int(8) COMMENT '分组数',
              `submitWay` enum('person','group') NOT NULL DEFAULT 'person' COMMENT '结果提交方式',
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
        $biz['db']->exec('drop table activity_brain_storm');
    }
}
