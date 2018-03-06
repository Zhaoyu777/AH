<?php

use Phpmig\Migration\Migration;

class CzieSyncJob extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
          CREATE TABLE `czie_sync_job` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `status` enum('created','syncing','succeed','fail') NOT NULL DEFAULT 'created' COMMENT '同步状态',
              `opUserId` int(10),
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
