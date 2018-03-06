<?php

use Phpmig\Migration\Migration;

class DisplayWallStatus extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE `activity_display_way_status` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `activityId` int(10) NOT NULL,
              `courseTaskId` int(10) NOT NULL,
              `status` enum('start','end') NOT NULL DEFAULT 'start' COMMENT '活动状态',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table activity_display_way_status');
    }
}
