<?php

use Phpmig\Migration\Migration;

class PracticeLikePost extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_practice_post` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `contentId` int(10) unsigned NOT NULL,
              `userId` int(10) unsigned NOT NULL,
              `parentId` int(10) unsigned,
              `content` text COMMENT '评论',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS `activity_practice_like` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `contentId` int(10) unsigned NOT NULL,
              `userId` int(10) unsigned NOT NULL,
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞时间',
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
        $biz['db']->exec('drop table activity_practice_post');
        $biz['db']->exec('drop table activity_practice_like');
    }
}
