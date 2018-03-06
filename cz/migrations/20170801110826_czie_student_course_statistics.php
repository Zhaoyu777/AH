<?php

use Phpmig\Migration\Migration;

class CzieStudentCourseStatistics extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_student_course_statistics` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `userId` int(10) NOT NULL,
                `courseId` int(10) NOT NULL DEFAULT '0' COMMENT '课程id',
                `studentAttendence` float(10,1) DEFAULT '0.0' COMMENT '平均出勤率',
                `taskInCompletionRate` float(10,1) DEFAULT '0.0' COMMENT '课堂互动完成率',
                `taskOutCompletionRate` float(10,1) DEFAULT '0.0' COMMENT '课外活动完成率',
                `averageGrades` float(10,1) DEFAULT '0.0' COMMENT '平时成绩',
                `totalScore` int(10) DEFAULT '0' COMMENT '总积分',
                `createdTime` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后更新时间',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='学生-课程学习数据';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table czie_student_course_statistics');
    }
}
