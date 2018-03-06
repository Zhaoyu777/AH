<?php

use Phpmig\Migration\Migration;

class ScoreTaskId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('czie_user_score', 'taskId')) {
            $biz['db']->exec("
                ALTER TABLE czie_user_score ADD `taskId` int(11) AFTER `courseId`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('czie_user_score', 'taskId')) {
            $biz['db']->exec("
                ALTER TABLE `czie_user_score` DROP COLUMN `taskId`;
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
