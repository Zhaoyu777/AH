<?php

use Phpmig\Migration\Migration;

class TaskGroupMemberSeq extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('czie_task_group_member', 'seq')) {
            $biz['db']->exec("
                ALTER TABLE czie_task_group_member ADD `seq` int(11) DEFAULT 1 AFTER `taskId`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('czie_task_group_member', 'seq')) {
            $biz['db']->exec("
                ALTER TABLE `czie_task_group_member` DROP COLUMN `seq`;
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
