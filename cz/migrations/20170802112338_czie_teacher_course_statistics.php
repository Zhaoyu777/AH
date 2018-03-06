<?php

use Phpmig\Migration\Migration;

class CzieTeacherCourseStatistics extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_teacher_course_statistics` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `userId` int(10) NOT NULL,
                `courseLessonRate` float DEFAULT '0' COMMENT '课程备课率',
                `lessonRate` float DEFAULT '0' COMMENT '课次备课率',
                `studentAttendRate` float DEFAULT '0' COMMENT '学生出勤率',
                `taskOuterCompletionRate` float DEFAULT '0' COMMENT '课外活动成率',
                `taskInCompletionRate` float DEFAULT '0' COMMENT '课堂活动完成率',
                `loginDays` int(10) DEFAULT '0' COMMENT '平台登录天数',
                `homeworkNum` int(10) DEFAULT '0' COMMENT '作业布置次数',
                `analysisNum` int(10) DEFAULT '0' COMMENT '课堂报告份数',
                `resourcesNum` int(10) DEFAULT '0' COMMENT '个人资源总数',
                `resourcesIncreaseNum` int(10) DEFAULT '0' COMMENT '资源同比增长数',
                `resourcesQuoteNum` int(10) DEFAULT '0' COMMENT '资源被引用数',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0'COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0'COMMENT '最后更新时间',
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
        $biz['db']->exec('drop table czie_teacher_course_statistics');
    }
}
