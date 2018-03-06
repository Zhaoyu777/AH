<?php

use Phpmig\Migration\Migration;

class UpdateSignInCourseId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec("
            update `czie_lesson_signin_member` s left join czie_course_lesson l on s.lessonId = l.id set s.courseId = l.courseId where s.lessonId = l.id;
        ");

        $connection->exec("
            update `czie_lesson_signin` s left join czie_course_lesson l on s.lessonId = l.id set s.courseId = l.courseId where s.lessonId = l.id;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
