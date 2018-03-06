<?php

use Phpmig\Migration\Migration;

class CzitCourse extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `czie_term` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '学期ID',
              `title` varchar(255) NOT NULL COMMENT '学期标题',
              `short_code`  varchar(255) NOT NULL COMMENT '学期代码',
              `long_code`   varchar(255) NOT NULL COMMENT '补全的学期代码',
              `current` boolean NOT NULL DEFAULT false COMMENT '是否当前学期',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`),
              UNIQUE `term_long_code` (`long_code`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS `czie_student_group` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `title`  varchar(255) NOT NULL COMMENT '分组名称',
              `seq`    int(10) UNSIGNED NOT NULL COMMENT '排序',
              `number` int(10) UNSIGNED NOT NULL COMMENT '编号',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '课程学员分组表';

            CREATE TABLE IF NOT EXISTS `czie_student_group_member` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `group_id`  int(10) unsigned NOT NULL COMMENT '分组ID',
              `course_member_id` int(10) unsigned NOT NULL COMMENT '课程成员ID',
              `seq`    int(10) UNSIGNED NOT NULL COMMENT '排序',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '课程学员分组成员表';

            CREATE TABLE IF NOT EXISTS `czie_course_lesson` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `course_id`  int(10) unsigned NOT NULL COMMENT '课程ID',
              `number` int(10) UNSIGNED NOT NULL COMMENT '编号',
              `seq`    int(10) UNSIGNED COMMENT '排序',
              `title`  varchar(255) COMMENT '课次标题',
              `teach_aim` text COMMENT '教学目的',
              `ability_aim` text COMMENT '能力目标',
              `aknowledge_aim` text COMMENT '知识目标',
              `quality_aim` text COMMENT '素质目标',
              `tasks_case`  text COMMENT '任务与案例',
              `difficult`  text COMMENT '重点重点与解决方案',
              `reference_material`  text COMMENT '参考资料',
              `after_know`  text COMMENT '课后体会',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '班级课次表';

            CREATE TABLE IF NOT EXISTS `czie_course_lesson_chapter` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `course_id`  int(10) unsigned NOT NULL COMMENT '课程ID',
              `lesson_id`  int(10) unsigned NOT NULL COMMENT '课次ID',
              `seq`    int(10) UNSIGNED COMMENT '排序',
              `number` int(10) UNSIGNED NOT NULL COMMENT '编号',
              `title`  varchar(255) COMMENT '标题',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '班级课次环节';

            CREATE TABLE IF NOT EXISTS `czie_course_lesson_task` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `course_id`  int(10) unsigned NOT NULL COMMENT '课程ID',
              `lesson_id`  int(10) unsigned NOT NULL COMMENT '课次ID',
              `seq`    int(10) UNSIGNED COMMENT '排序',
              `stage` enum('before','in','after') COMMENT '阶段：课前，课中，课后',
              `chapter_id` int(10) unsigned NOT NULL COMMENT '环节ID',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '班级课次任务表';

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
