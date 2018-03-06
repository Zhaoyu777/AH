<?php

use Phpmig\Migration\Migration;

class TeachingAimsActivity extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `zhkt_lesson_teaching_aims_activity` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                `aimId` int(11) NOT NULL default '0' COMMENT '目标ID',
                `courseId` int(11) NOT NULL default '0' COMMENT '课程ID',
                `orgCode`  varchar(255) DEFAULT '' COMMENT '学院代码',
                `activityId` int(11) NOT NULL default '0' COMMENT '活动ID',
                `teacherId` int(11) NOT NULL default '0' COMMENT '教师ID',
                `termCode`  varchar(255) NOT NULL DEFAULT '' COMMENT '学期代码',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',   
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='教案目标表与活动表的中间表';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table zhkt_lesson_teaching_aims_activity');
    }
}
