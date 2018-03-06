<?php

use Phpmig\Migration\Migration;

class UpdateStatisticsDataType extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `courseLessonRate` DOUBLE(10,3) COMMENT '课程备课率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `lessonRate` DOUBLE(10,3) COMMENT '课次备课率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `studentAttendRate` DOUBLE(10,3) COMMENT '学生出勤率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `taskOuterCompletionRate` DOUBLE(10,3) DEFAULT '0' COMMENT '课外活动成率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `taskInCompletionRate` DOUBLE(10,3) COMMENT '课堂活动完成率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `teachingAimsFinishedRate` DOUBLE(10,3) DEFAULT '0' COMMENT '教学目标达成率 ';");
        

        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `studentAttendRate` DOUBLE(10,3) DEFAULT '0' COMMENT '学生出勤率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskInCompletionRate` DOUBLE(10,3) COMMENT '课堂活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskAfterCompletionRate` DOUBLE(10,3) DEFAULT '0' COMMENT '课后活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskBeforeCompletionRate` DOUBLE(10,3) DEFAULT '0' COMMENT '课前活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `teachingAimsFinishedRate` DOUBLE(10,3) DEFAULT '0' COMMENT '教学目标达成率 ';");

        $connection->exec("ALTER TABLE `czie_student_course_statistics` MODIFY COLUMN `studentAttendence` DOUBLE(10,1) DEFAULT '0' COMMENT '平均出勤率';");
        $connection->exec("ALTER TABLE `czie_student_course_statistics` MODIFY COLUMN `taskInCompletionRate` DOUBLE(10,1) DEFAULT '0' COMMENT '课堂互动完成率';");
        $connection->exec("ALTER TABLE `czie_student_course_statistics` MODIFY COLUMN `taskOutCompletionRate` DOUBLE(10,1) DEFAULT '0' COMMENT '课外活动完成率';");

        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `courseLessonRate` DOUBLE(10,3) COMMENT '课程备课率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `lessonRate` DOUBLE(10,3) COMMENT '课次备课率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `studentAttendRate` DOUBLE(10,3) COMMENT '学生出勤率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `taskOuterCompletionRate` DOUBLE(10,3) DEFAULT '0' COMMENT '课外活动成率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `taskInCompletionRate` DOUBLE(10,3) COMMENT '课堂活动完成率';");
        $connection->exec("ALTER TABLE `czie_teacher_course_statistics` MODIFY COLUMN `teachingAimsFinishedRate` DOUBLE(10,3) DEFAULT '0' COMMENT '教学目标达成率 ';");
        

        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `studentAttendRate` DOUBLE(10,3) DEFAULT '0' COMMENT '学生出勤率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskInCompletionRate` DOUBLE(10,3) COMMENT '课堂活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskAfterCompletionRate` DOUBLE(10,3) DEFAULT '0' COMMENT '课后活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `taskBeforeCompletionRate` DOUBLE(10,3) DEFAULT '0' COMMENT '课前活动完成率';");
        $connection->exec("ALTER TABLE `czie_course_statistics` MODIFY COLUMN `teachingAimsFinishedRate` DOUBLE(10,3) DEFAULT '0' COMMENT '教学目标达成率 ';");

        $connection->exec("ALTER TABLE `czie_student_course_statistics` MODIFY COLUMN `studentAttendence` DOUBLE(10,1) DEFAULT '0' COMMENT '平均出勤率';");
        $connection->exec("ALTER TABLE `czie_student_course_statistics` MODIFY COLUMN `taskInCompletionRate` DOUBLE(10,1) DEFAULT '0' COMMENT '课堂互动完成率';");
        $connection->exec("ALTER TABLE `czie_student_course_statistics` MODIFY COLUMN `taskOutCompletionRate` DOUBLE(10,1) DEFAULT '0' COMMENT '课外活动完成率';");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
