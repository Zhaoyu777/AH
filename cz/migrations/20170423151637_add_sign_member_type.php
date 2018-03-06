<?php

use Phpmig\Migration\Migration;

class AddSignMemberType extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('czie_lesson_signin_member', 'type')) {
            $biz['db']->exec("
                ALTER TABLE czie_lesson_signin_member ADD `type` enum('default','add') NOT NULL DEFAULT 'default' COMMENT '记录来源';
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('czie_lesson_signin_member', 'type')) {
            $biz['db']->exec("
                ALTER TABLE `czie_lesson_signin_member` DROP COLUMN `type`;
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
