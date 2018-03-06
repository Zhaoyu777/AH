<?php

use Phpmig\Migration\Migration;

class CzieCourseSetAddUser extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $db  = $biz['db'];

        if (!$this->isFieldExist('course_set_v8', 'teacherId')) {
            $db->exec(
                "ALTER TABLE course_set_v8 Add COLUMN userId int(10) COMMENT '所属老师';"
            );
            $db->exec(
                "ALTER TABLE course_set_v8 Add COLUMN courseNo varchar(32) COMMENT '课程代码';"
            );
        }
    }

    protected function isFieldExist($table, $filedName)
    {
        $biz = $this->getContainer();
        $db  = $biz['db'];

        $sql    = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $db->fetchAssoc($sql);

        return empty($result) ? false : true;
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
