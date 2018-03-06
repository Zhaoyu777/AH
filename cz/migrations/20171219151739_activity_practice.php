<?php

use Phpmig\Migration\Migration;

class ActivityPractice extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_practice` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `fileIds` varchar(1024) DEFAULT NULL COMMENT '预览资料', 
              `mediaCount` int(10) NOT NULL DEFAULT '0' COMMENT '资料数',
              `createdUserId` int(11) NOT NULL,
              `createdTime` int(10) DEFAULT 0,
              `updatedTime` int(11) DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='练一练活动';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
