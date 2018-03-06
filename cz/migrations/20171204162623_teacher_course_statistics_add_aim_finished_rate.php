<?php

use Phpmig\Migration\Migration;

class TeacherCourseStatisticsAddAimFinishedRate extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            ALTER TABLE `czie_teacher_course_statistics` Add `teachingAimsFinishedRate` float(10,3) DEFAULT '0' COMMENT '教学目标达成率' AFTER `resourcesQuoteNum`;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
