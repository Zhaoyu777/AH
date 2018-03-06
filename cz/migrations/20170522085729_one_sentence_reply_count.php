<?php

use Phpmig\Migration\Migration;

class OneSentenceReplyCount extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('activity_one_sentence_result', 'replyCount')) {
            $biz['db']->exec("
                ALTER TABLE activity_one_sentence_result ADD `replyCount` int(11) NOT NULL DEFAULT 0 COMMENT '该组应答人数' AFTER `groupId`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('activity_one_sentence_result', 'replyCount')) {
            $biz['db']->exec("
                ALTER TABLE `activity_one_sentence_result` DROP COLUMN `replyCount`;
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
