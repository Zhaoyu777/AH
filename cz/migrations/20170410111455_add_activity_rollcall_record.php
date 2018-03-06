<?php

use Phpmig\Migration\Migration;

class AddActivityRollcallRecord extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
          CREATE TABLE `activity_rollcall_result` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `activityId` int(10) NOT NULL,
              `courseId` int(10) NOT NULL,
              `courseTaskId` int(10) NOT NULL,
              `userId` int(10) NOT NULL,
              `score` int(10) NOT NULL DEFAULT '0' COMMENT '评分',
              `remark` text COMMENT '评论',
              `opUserId` int(10) NOT NULL,
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

            CREATE TABLE `czie_user_score` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `courseId` int(10),
              `term` varchar(255) NOT NULL COMMENT '学期代码(short)',
              `userId` int(10) NOT NULL,
              `targetType` varchar(64) NOT NULL DEFAULT 'course' COMMENT '类型',
              `targetId` INT(10) UNSIGNED NOT NULL COMMENT 'id',
              `score` int(10) NOT NULL DEFAULT '0' COMMENT '分数',
              `remark` text COMMENT '描述',
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
        $biz['db']->exec('drop table activity_rollcall_result');
        $biz['db']->exec('drop table czie_user_score');
    }
}
