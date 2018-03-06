<?php

use Phpmig\Migration\Migration;

class TeachingAimActivityAddLessonid extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->isFieldExist('zhkt_lesson_teaching_aims_activity', 'lessonId')) {
            $biz = $this->getContainer();
            $biz['db']->exec("
                ALTER TABLE `zhkt_lesson_teaching_aims_activity` ADD `lessonId` int(11) NOT NULL default '0' COMMENT '课次ID' AFTER `courseId`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }

    protected function isFieldExist($table, $filedName)
    {
        $biz = $this->getContainer();

        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
