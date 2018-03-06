<?php

use Phpmig\Migration\Migration;

class CzieStudentGroupCourseId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->fieldExists('czie_student_group', 'course_id')) {
            $biz['db']->exec('
                ALTER TABLE `czie_student_group` ADD `course_id` int(10) DEFAULT null AFTER `title` ;
            ');
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->fieldExists('czie_student_group', 'course_id')) {
            $biz['db']->exec('
                ALTER TABLE `czie_student_group` DROP COLUMN `course_id`;
            ');
        }
    }

    private function fieldExists($table, $field)
    {
        $biz = $this->getContainer();
        $sql = "DESCRIBE `{$table}` `{$field}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
