<?php

use Phpmig\Migration\Migration;

class PrepareCourseLog extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
          CREATE TABLE `czie_prepare_course_log` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `courseId` int(10),
              `userId` int(10),
              `termCode` VARCHAR(255),
              `message` text NOT NULL COMMENT '日志内容',
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
        $connection = $biz['db'];
        $connection->exec('DROP TABLE IF EXISTS `czie_prepare_course_log`');
    }
}
