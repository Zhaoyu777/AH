<?php

use Phpmig\Migration\Migration;

class CourseStatisticsAddTeachingAimsFinishedRate extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->isFieldExist('czie_course_statistics', 'teachingAimsFinishedRate')) {
            $biz = $this->getContainer();
            $biz['db']->exec("
                ALTER TABLE `czie_course_statistics` ADD `teachingAimsFinishedRate` float(10,3) DEFAULT '0' COMMENT '教学目标达成率' AFTER `totalScore`;
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
