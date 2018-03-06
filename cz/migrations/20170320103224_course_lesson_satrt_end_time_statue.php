<?php

use Phpmig\Migration\Migration;

class CourseLessonSatrtEndTimeStatue extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->fieldExists('czie_course_lesson', 'status')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson` ADD `status` ENUM("created","teaching","teached") NOT NULL DEFAULT "created" COMMENT "状态" AFTER `title`;
            ');
        }

        if (!$this->fieldExists('czie_course_lesson', 'endTime')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson` ADD `endTime` int(10) unsigned NOT NULL DEFAULT "0" COMMENT "结束上课时间" AFTER `after_know`;
            ');
        }

        if (!$this->fieldExists('czie_course_lesson', 'startTime')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson` ADD `startTime` int(10) unsigned NOT NULL DEFAULT "0" COMMENT "开始上课时间" AFTER `after_know`;
            ');
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->fieldExists('czie_course_lesson', 'status')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson` DROP COLUMN `status`;
            ');
        }

        if ($this->fieldExists('czie_course_lesson', 'startTime')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson` DROP COLUMN `startTime`;
            ');
        }

        if ($this->fieldExists('czie_course_lesson', 'endTime')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson` DROP COLUMN `endTime`;
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
