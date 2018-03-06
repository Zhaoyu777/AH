<?php

use Phpmig\Migration\Migration;

class CzieTermType extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('czie_term', 'isVisible')) {
            $biz['db']->exec("
                ALTER TABLE czie_term ADD `isVisible` tinyint(2) NOT NULL DEFAULT '1' COMMENT '可见与否，默认为可见' AFTER `current`;
            ");
            $biz['db']->exec("
                UPDATE czie_term SET `isVisible` = 0 WHERE `current` = 0;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('czie_term', 'isVisible')) {
            $biz['db']->exec("
                ALTER TABLE `czie_term` DROP COLUMN `isVisible`;
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
