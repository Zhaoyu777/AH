<?php

use Phpmig\Migration\Migration;

class TeachingLessonRecord extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE `teaching_lesson_record` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `courseSetId` int(10) NOT NULL DEFAULT 0,
                `courseId` int(10) NOT NULL DEFAULT 0,
                `lessonId` int(10) NOT NULL DEFAULT 0 COMMENT '课次ID',
                `taskId` int(10) NOT NULL DEFAULT 0 COMMENT '任务ID',
                `teacherId` int(10) NOT NULL DEFAULT 0 COMMENT '老师ID',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
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
        $biz['db']->exec('drop table teaching_lesson_record');
    }
}
