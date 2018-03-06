<?php

use Phpmig\Migration\Migration;

class AddCzieTaskTaskId extends Migration
{
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->fieldExists('czie_course_lesson_task', 'task_id')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson_task` ADD `task_id` int(10) DEFAULT null AFTER `lesson_id` ;
            ');
        }
    }

    public function down()
    {
        $biz = $this->getContainer();

        if ($this->fieldExists('czie_course_lesson_task', 'task_id')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson_task` DROP COLUMN `task_id`;
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
