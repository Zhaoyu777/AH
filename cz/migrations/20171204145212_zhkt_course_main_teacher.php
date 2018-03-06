<?php

use Phpmig\Migration\Migration;

class ZhktCourseMainTeacher extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `zhkt_course_main_teacher` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                `courseId` int(11) NOT NULL default '0' COMMENT '课程ID',
                `teacherId` int(11) NOT NULL default '0' COMMENT '教师ID',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',   
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程主带老师表';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table zhkt_course_main_teacher');
    }
}
