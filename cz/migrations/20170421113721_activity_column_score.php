<?php

use Phpmig\Migration\Migration;

class ActivityColumnScore extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('activity', 'score')) {
            $biz['db']->exec("
                ALTER TABLE activity ADD `score` int(11) DEFAULT '0' COMMENT '完成时得分' AFTER `content`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('activity', 'score')) {
            $biz['db']->exec("
                ALTER TABLE `activity` DROP COLUMN `score`;
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
