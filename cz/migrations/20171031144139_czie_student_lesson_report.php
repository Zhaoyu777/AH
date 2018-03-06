<?php

use Phpmig\Migration\Migration;

class CzieStudentLessonReport extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_student_lesson_report` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `courseId` int(10) NOT NULL,
                `lessonId` int(10) NOT NULL,
                `userId` int(10) NOT NULL,
                `taskInCompletionRate` float(10,1) DEFAULT '0.0' COMMENT '课堂活动完成率',
                `taskBeforCompletionRate` float(10,1) DEFAULT '0.0' COMMENT '课前活动完成率',
                `exerciseNumber` int(10) DEFAULT '0' COMMENT '互动次数',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='学生课堂数据';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table czie_student_lesson_report');
    }
}
