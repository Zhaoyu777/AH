<?php

use Phpmig\Migration\Migration;

class RandomTestpaperResult extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE `random_testpaper` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `courseId` int(10) NOT NULL COMMENT '班级Id',
                `lessonId` int(10) NOT NULL COMMENT '课次Id',
                `taskId` int(10) NOT NULL COMMENT '任务id',
                `activityId` int(10) NOT NULL COMMENT '活动id',
                `userId` int(10) NOT NULL,
                `doTime` int(10) NOT NULL DEFAULT 1 COMMENT '第几次测验',
                `score` float(10,1) NOT NULL DEFAULT '0.0' COMMENT '个人得分',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ");

        $biz['db']->exec("
            CREATE TABLE `random_testpaper_item` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `testId` int(10) NOT NULL COMMENT '试卷Id',
                `questionId` int(10) NOT NULL COMMENT '题目id',
                `seq` int(10) NOT NULL COMMENT '题目顺序',
                `realScore` float(10,1) NOT NULL DEFAULT '0.0' COMMENT '实际得分',
                `answer` text COMMENT '答案',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
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
        $biz['db']->exec('drop table random_testpaper');
        $biz['db']->exec('drop table random_testpaper_item');
    }
}
