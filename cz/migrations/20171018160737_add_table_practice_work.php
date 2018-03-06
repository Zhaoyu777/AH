<?php

use Phpmig\Migration\Migration;

class AddTablePracticeWork extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_practice_work` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `fileType` varchar(24) NOT NULL DEFAULT '',
                `createdUserId` int(10) NOT NULL COMMENT '创建人',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='实践作业活动';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table activity_practice_work');
    }
}
