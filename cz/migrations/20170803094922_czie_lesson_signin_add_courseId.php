<?php

use Phpmig\Migration\Migration;

class CzieLessonSigninAddCourseId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->isFieldExist('czie_lesson_signin', 'courseId')) {
            $biz['db']->exec("
                ALTER TABLE `czie_lesson_signin` ADD `courseId` int(10) NOT NULL DEFAULT '0' COMMENT '课程Id';
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('czie_lesson_signin', 'courseId')) {
            $biz['db']->exec('
                ALTER TABLE `czie_lesson_signin` DROP COLUMN `courseId`;
            ');
        }
    }

    protected function isFieldExist($table, $filedName)
    {
        $biz = $this->getContainer();

        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
