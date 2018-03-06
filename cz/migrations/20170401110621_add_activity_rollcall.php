<?php

use Phpmig\Migration\Migration;

class AddActivityRollcall extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->isFieldExist('activity', 'about')) {
            $biz['db']->exec("
                ALTER TABLE activity ADD `about` text COMMENT '教学说明' AFTER `content`;
            ");
        }

        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_rollcall` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `duration` int(11) NOT NULL DEFAULT '0' COMMENT '参考时长',
              `createdTime` int(10) DEFAULT 0,
              `createdUserId` int(11) NOT NULL,
              `updatedTime` int(11) DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('activity', 'about')) {
            $biz['db']->exec("
                ALTER TABLE `activity` DROP COLUMN `about`;
            ");
        }

        $biz['db']->exec('DROP TABLE IF EXISTS `activity_rollcall`');
    }

    protected function isFieldExist($table, $filedName)
    {
        $biz = $this->getContainer();

        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
