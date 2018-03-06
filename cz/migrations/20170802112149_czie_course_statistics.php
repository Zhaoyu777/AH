<?php

use Phpmig\Migration\Migration;

class CzieCourseStatistics extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_course_statistics` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `courseId` int(10) NOT NULL,
                `lessonId` int(10) NOT NULL,
                `studentAttendRate` float DEFAULT '0' COMMENT '学生出勤率',
                `taskInCompletionRate` float DEFAULT '0' COMMENT '课堂活动完成率',
                `taskAfterCompletionRate` float DEFAULT '0' COMMENT '课后活动完成率',
                `taskBeforeCompletionRate` float DEFAULT '0' COMMENT '课前活动完成率',
                `evaluationScore` float DEFAULT '0' COMMENT '课程评价平均分',
                `totalScore` int(10) DEFAULT '0' COMMENT '课次总积分',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) COMMENT='所有课程统计';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table czie_course_statistics');
    }
}
