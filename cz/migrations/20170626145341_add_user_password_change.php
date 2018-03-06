<?php

use Phpmig\Migration\Migration;

class AddUserPasswordChange extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->isFieldExist('user', 'passwordChange')) {
            $biz['db']->exec("
                ALTER TABLE user ADD `passwordChange` TINYINT(1) NOT NULL DEFAULT '1'  COMMENT '是否修改密码;1是，0否';
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('user', 'passwordChange')) {
            $biz['db']->exec("
                ALTER TABLE `user` DROP COLUMN `passwordChange`;
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
