<?php

use Phpmig\Migration\Migration;

class AddTeachedStudentCount extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `czie_course_lesson` ADD `memberCount` int(10) DEFAULT 0");

        $connection->exec("UPDATE `czie_course_lesson` l LEFT JOIN (SELECT count(*) count, courseId FROM `course_member` WHERE role = 'student' GROUP BY courseId) t ON l.courseId = t.courseId SET l.memberCount = t.count WHERE l.status = 'teached'");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `czie_course_lesson` DROP COLUMN `memberCount`");
    }
}
