<?php

use Phpmig\Migration\Migration;

class CzieTaskGroup extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_task_group` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `title`  varchar(255) NOT NULL COMMENT '分组名称',
              `taskId` int(10) NOT NULL,
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '活动分组表';

            CREATE TABLE IF NOT EXISTS `czie_task_group_member` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `groupId`  int(10) unsigned NOT NULL COMMENT '分组ID',
              `userId` int(10) unsigned NOT NULL,
              `taskId` int(10) NOT NULL,
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '活动分组成员表';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table czie_task_group');
        $biz['db']->exec('drop table czie_task_group_member');
    }
}
