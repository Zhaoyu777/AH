<?php

use Phpmig\Migration\Migration;

class ScoreLessonId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('czie_user_score', 'lessonId')) {
            $biz['db']->exec("
                ALTER TABLE czie_user_score ADD `lessonId` int(11) AFTER `courseId`;
            ");
        }

        if (!$this->isFieldExist('czie_user_score', 'type')) {
            $biz['db']->exec("
                ALTER TABLE czie_user_score ADD `type` varchar(255) DEFAULT 'auto' AFTER `lessonId`;

                UPDATE czie_user_score SET `type` = 'operate' WHERE `targetType` IN ('brainStorm','raceAnswer','displayWall','rollcall');
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('czie_user_score', 'lessonId')) {
            $biz['db']->exec("
                ALTER TABLE `czie_user_score` DROP COLUMN `lessonId`;
            ");
        }

        if ($this->isFieldExist('czie_user_score', 'type')) {
            $biz['db']->exec("
                ALTER TABLE `czie_user_score` DROP COLUMN `type`;
            ");
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
