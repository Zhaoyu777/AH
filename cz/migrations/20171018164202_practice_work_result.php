<?php

use Phpmig\Migration\Migration;

class PracticeWorkResult extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `practice_work_result` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `practiceWorkId` int(10) NOT NULL COMMENT '实践作业id',
              `fileId` int(10) NOT NULL COMMENT '文件id',
              `userId` int(10) NOT NULL COMMENT '做实践作业的人',
              `taskId` int(10) NOT NULL COMMENT '所属任务',
              `activityId` int(10) NOT NULL COMMENT '所属活动',
              `checkTeacherId` int(10) NULL COMMENT '批阅老师',
              `appraisal` int(10) NULL DEFAULT NULL COMMENT '评价等级',
              `comment` text NULL DEFAULT NULL COMMENT '评语',
              `status` enum('reviewing','finished') DEFAULT 'reviewing' COMMENT '批阅状态',
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
        
        $biz['db']->exec('DROP TABLE IF EXISTS `practice_work_result`');
    }
}
