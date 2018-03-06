<?php

use Phpmig\Migration\Migration;

class AddCourseSetTeacher extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec("update course_set_v8 cs,(select userId,courseSetId from course_member where id in (select min(id) from course_member where role = 'teacher' group by courseSetId)) cm set cs.userId = cm.userId where cs.id = cm.courseSetId and cs.type = 'instant' and courseNo is null");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
