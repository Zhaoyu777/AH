<?php

use Phpmig\Migration\Migration;

class CzieCourseContinuousWarning extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `zhkt_course_continuous_warning` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                `courseId` int(11) NOT NULL default '0' COMMENT '课程ID',
                `continuous` int(11) NOT NULL default '0' COMMENT '连续预警次数',
                `type` enum('taskInCompleRate','teachingAimFinished') NOT NULL COMMENT '目标类型 testInComple：任务参与率，teachingAimFinished：目标达成',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='连续预警记录表';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
