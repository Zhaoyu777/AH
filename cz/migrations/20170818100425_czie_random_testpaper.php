<?php

use Phpmig\Migration\Migration;

class CzieRandomTestpaper extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `activity_random_testpaper` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
                `description` text COMMENT '说明',
                `totalScore` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '总分',
                `passedScore` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '通过考试的分数线',
                `itemCount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '题目数量',
                `metas` text COMMENT '题型排序',
                `createdUserId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
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
        $biz['db']->exec('drop table activity_random_testpaper');
    }
}
