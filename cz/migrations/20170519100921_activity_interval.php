<?php

use Phpmig\Migration\Migration;

class ActivityInterval extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec("
           CREATE TABLE IF NOT EXISTS `activity_interval` (
              `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
              `mediaSource` varchar(32) NOT NULL DEFAULT '' COMMENT '媒体文件来源(self:本站上传,youku:优酷)',
              `mediaId` int(10) NOT NULL DEFAULT 0 COMMENT '媒体文件ID',
              `mediaUri` text COMMENT '媒体文件资UR',
              `createdTime` int(10) DEFAULT 0,
              `updatedTime` int(11) DEFAULT 0,
               PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='课间活动扩展表';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table activity_interval');
    }
}
