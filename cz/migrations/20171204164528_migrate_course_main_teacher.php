<?php

use Phpmig\Migration\Migration;

class MigrateCourseMainTeacher extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $db = $biz['db'];

        $db->exec("INSERT INTO `zhkt_course_main_teacher` (teacherId, courseId) SELECT userId, courseId FROM `course_member` WHERE id IN (select min(id) from `course_member` WHERE role = 'teacher' group by courseId)");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
