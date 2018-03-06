<?php

use Phpmig\Migration\Migration;

class LessonSignin extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `czie_lesson_signin` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `lesson_id` int(10) unsigned NOT NULL COMMENT '课次ID',
              `time`  int(10) unsigned NOT NULL COMMENT '第多少次签到',
              `verify_code` varchar(255) NOT NULL COMMENT '签到码',
              `status` enum('start','end') COMMENT '状态：开始，结束',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS `czie_lesson_signin_member` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `lesson_id` int(10) unsigned NOT NULL COMMENT '课次ID',
              `time`  int(10) unsigned NOT NULL COMMENT '第多少次签到',
              `signin_id` int(10) unsigned NOT NULL COMMENT '签到ID',
              `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
              `status` enum('absent','attend') COMMENT '状态：缺勤，出勤',
              `lng` varchar(255) COMMENT '签到经度',
              `lat` varchar(255) COMMENT '签到纬度',
              `address` varchar(1024) COMMENT '签到地址',
              `op_user_id` int(10) unsigned COMMENT '操作用户ID',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ";
        $container = $this->getContainer();
        $container['db']->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table czie_lesson_signin');
        $biz['db']->exec('drop table czie_lesson_signin_member');
    }
}
