<?php

use Phpmig\Migration\Migration;

class DisplayWallResultComment extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_display_wall_result` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `activityId` int(10) NOT NULL,
              `courseId` int(10) NOT NULL,
              `courseTaskId` int(10) NOT NULL,
              `userId` int(10) NOT NULL,
              `score` int(10) NOT NULL DEFAULT '0' COMMENT '评分',
              `remark` text COMMENT '评论',
              `opUserId` int(10) NOT NULL,
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS `activity_display_wall_content` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `resultId` int(10) unsigned NOT NULL,
              `uri` varchar(255) COMMENT '图片路径',
              `likeNum` int(10) NOT NULL DEFAULT '0' COMMENT '点赞数',
              `postNum` int(10) NOT NULL DEFAULT '0' COMMENT '回复数',
              `userId` int(10) NOT NULL,
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS `activity_display_wall_post` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `contentId` int(10) unsigned NOT NULL,
              `userId` int(10) unsigned NOT NULL,
              `parentId` int(10) unsigned,
              `content` text COMMENT '评论',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS `activity_display_wall_like` (
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
        $biz['db']->exec('drop table activity_display_wall_result');
        $biz['db']->exec('drop table activity_display_wall_content');
        $biz['db']->exec('drop table activity_display_wall_post');
        $biz['db']->exec('drop table activity_display_wall_like');
    }
}
