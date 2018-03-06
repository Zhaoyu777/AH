<?php

use Phpmig\Migration\Migration;

class CreateActivityQuestionnaire extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_questionnaire` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `mediaId` int(10) NOT NULL COMMENT '调查问卷id',
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
        $biz = $this->getContainer();
        
        $biz['db']->exec('DROP TABLE IF EXISTS `questionnaire`');
    }
}
