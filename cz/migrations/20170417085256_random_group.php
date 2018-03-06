<?php

use Phpmig\Migration\Migration;

class RandomGroup extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_random_group_member` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `taskId` int(10) NOT NULL,
              `groupNum` int(10) NOT NULL,
              `userId` int(10) NOT NULL,
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ");

        if (!$this->isFieldExist('activity', 'duration')) {
            $biz['db']->exec("
                ALTER TABLE activity ADD `duration` int(11) NOT NULL DEFAULT '0' COMMENT '参考时长' AFTER `content`;
            ");
        }

        if ($this->isFieldExist('activity_rollcall', 'duration')) {
            $biz['db']->exec("
                ALTER TABLE `activity_rollcall` DROP COLUMN `duration`;
            ");
        }

        if ($this->isFieldExist('activity_display_wall', 'duration')) {
            $biz['db']->exec("
                ALTER TABLE `activity_display_wall` DROP COLUMN `duration`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('activity', 'duration')) {
            $biz['db']->exec("
                ALTER TABLE `activity` DROP COLUMN `duration`;
            ");
        }

        if (!$this->isFieldExist('activity_rollcall', 'duration')) {
            $biz['db']->exec("
                ALTER TABLE activity_rollcall ADD `duration` int(11) NOT NULL DEFAULT '0' COMMENT '参考时长';
            ");
        }

        if (!$this->isFieldExist('activity_display_wall', 'duration')) {
            $biz['db']->exec("
                ALTER TABLE activity_display_wall ADD `duration` int(11) NOT NULL DEFAULT '0' COMMENT '参考时长';
            ");
        }

        $biz['db']->exec('DROP TABLE IF EXISTS `czie_random_group_member`');
    }

    protected function isFieldExist($table, $filedName)
    {
        $biz = $this->getContainer();

        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
