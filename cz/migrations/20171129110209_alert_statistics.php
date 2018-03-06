<?php

use Phpmig\Migration\Migration;

class AlertStatistics extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` ADD `termCode` varchar(255);");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `courseLessonRate` float(10,3) COMMENT '课程备课率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `lessonRate` float(10,3) COMMENT '课次备课率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `studentAttendRate` float(10,3) COMMENT '学生出勤率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `taskOuterCompletionRate` float(10,3) DEFAULT '0' COMMENT '课外活动成率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `taskInCompletionRate` float(10,3) COMMENT '课堂活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `studentAttendRate` float(10,3) DEFAULT '0' COMMENT '学生出勤率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskInCompletionRate` float(10,3) COMMENT '课堂活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskAfterCompletionRate` float(10,3) DEFAULT '0' COMMENT '课后活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskBeforeCompletionRate` float(10,3) DEFAULT '0' COMMENT '课前活动完成率';");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
