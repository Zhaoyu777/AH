<?php

use Phpmig\Migration\Migration;

class OneSentenceResult extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_one_sentence_result` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `activityId` int(10) NOT NULL,
              `courseId` int(10) NOT NULL,
              `courseTaskId` int(10) NOT NULL,
              `userId` int(10) NOT NULL,
              `groupId` int(10),
              `content` text COMMENT '回复内容',
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
        $biz['db']->exec('drop table activity_one_sentence_result');
    }
}
