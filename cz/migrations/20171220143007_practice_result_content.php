<?php

use Phpmig\Migration\Migration;

class PracticeResultContent extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_practice_content` (
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
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table activity_practice_post');
    }
}
