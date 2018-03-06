<?php

use Phpmig\Migration\Migration;

class AddLessonTaskNum extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `czie_course_lesson` ADD `taskNum` int(10) DEFAULT 0");

        $connection->exec("UPDATE `czie_course_lesson` l LEFT JOIN (SELECT count(*) count, lessonId FROM `czie_course_lesson_task` GROUP BY lessonId) t ON l.id = t.lessonId SET l.taskNum = t.count");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `czie_course_lesson` DROP COLUMN `taskNum`");
    }
}
