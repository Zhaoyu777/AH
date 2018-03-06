<?php

use Phpmig\Migration\Migration;

class DisplayWall extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_display_wall` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `groupWay` enum('none','fixed','random') NOT NULL DEFAULT 'none' COMMENT '分组方式',
              `groupNumber` int(8) COMMENT '分组数',
              `submitWay` enum('person','group') NOT NULL DEFAULT 'person' COMMENT '结果提交方式',
              `duration` int(11) NOT NULL DEFAULT '0' COMMENT '参考时长',
              `score` int(2) NOT NULL DEFAULT '0' COMMENT '完成获得积分数',
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
        $biz['db']->exec('drop table activity_display_wall');
    }
}
