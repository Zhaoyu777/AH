<?php

use Phpmig\Migration\Migration;

class CzieCourseShare extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE `czie_course_share` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `courseId` int(10) NOT NULL COMMENT '课程Id',
                `toUserId` int(10) NOT NULL COMMENT '被分享教师Id',
                `fromUserId` int(10) NOT NULL COMMENT '分享教师Id',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
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
        $biz['db']->exec('drop table czie_course_share');
    }
}
