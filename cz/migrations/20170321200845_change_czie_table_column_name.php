<?php

use Phpmig\Migration\Migration;

class ChangeCzieTableColumnName extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "
            ALTER TABLE `czie_term` CHANGE `short_code` `shortCode`  varchar(255) NOT NULL COMMENT '学期代码';
            ALTER TABLE `czie_term` CHANGE `long_code` `longCode`   varchar(255) NOT NULL COMMENT '补全的学期代码';

            ALTER TABLE `czie_student_group_member` CHANGE `group_id` `groupId`  int(10) unsigned NOT NULL COMMENT '分组ID';
            ALTER TABLE `czie_student_group_member` CHANGE `course_member_id` `courseMemberId` int(10) unsigned NOT NULL COMMENT '课程成员ID';

            ALTER TABLE `czie_course_lesson` CHANGE `course_id` `courseId`  int(10) unsigned NOT NULL COMMENT '课程ID';
            ALTER TABLE `czie_course_lesson` CHANGE `teach_aim` `teachAim` text COMMENT '教学目的';
            ALTER TABLE `czie_course_lesson` CHANGE `ability_aim` `abilityAim` text COMMENT '能力目标';
            ALTER TABLE `czie_course_lesson` CHANGE `aknowledge_aim` `aknowledgeAim` text COMMENT '知识目标';
            ALTER TABLE `czie_course_lesson` CHANGE `quality_aim` `qualityAim` text COMMENT '素质目标';
            ALTER TABLE `czie_course_lesson` CHANGE `tasks_case` `tasksCase`  text COMMENT '任务与案例';
            ALTER TABLE `czie_course_lesson` CHANGE `reference_material` `referenceMaterial`  text COMMENT '参考资料';
            ALTER TABLE `czie_course_lesson` CHANGE `after_know` `afterKnow`  text COMMENT '课后体会';

            ALTER TABLE `czie_course_lesson_chapter` CHANGE `course_id` `courseId`  int(10) unsigned NOT NULL COMMENT '课程ID';
            ALTER TABLE `czie_course_lesson_chapter` CHANGE `lesson_id` `lessonId`  int(10) unsigned NOT NULL COMMENT '课次ID';

            ALTER TABLE `czie_course_lesson_task` CHANGE `course_id` `courseId`  int(10) unsigned NOT NULL COMMENT '课程ID';
            ALTER TABLE `czie_course_lesson_task` CHANGE `lesson_id` `lessonId`  int(10) unsigned NOT NULL COMMENT '课次ID';
            ALTER TABLE `czie_course_lesson_task` CHANGE `chapter_id` `chapterId` int(10) unsigned NOT NULL COMMENT '环节ID';

            ALTER TABLE `c2_course` CHANGE `term_code` `termCode` VARCHAR(255);

            ALTER TABLE `czie_course_lesson_task` CHANGE `task_id` `taskId` int(10) DEFAULT null;

            ALTER TABLE `czie_student_group` CHANGE `course_id` `courseId` int(10) DEFAULT null;

            ALTER TABLE `czie_lesson_signin` CHANGE `lesson_id` `lessonId` int(10) unsigned NOT NULL COMMENT '课次ID';
            ALTER TABLE `czie_lesson_signin` CHANGE `verify_code` `verifyCode` varchar(255) NOT NULL COMMENT '签到码';

            ALTER TABLE `czie_lesson_signin_member` CHANGE `lesson_id` `lessonId` int(10) unsigned NOT NULL COMMENT '课次ID';
            ALTER TABLE `czie_lesson_signin_member` CHANGE `signin_id` `signinId` int(10) unsigned NOT NULL COMMENT '签到ID';
            ALTER TABLE `czie_lesson_signin_member` CHANGE `user_id` `userId` int(10) unsigned NOT NULL COMMENT '用户ID';
            ALTER TABLE `czie_lesson_signin_member` CHANGE `op_user_id` `opUserId` int(10) unsigned COMMENT '操作用户ID';
        ";

        $container = $this->getContainer();
        $container['db']->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
