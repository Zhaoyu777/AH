<?php

use Phpmig\Migration\Migration;

class CzieTeacherScore extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_teacher_score` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `courseId` int(10) NOT NULL,
                `lessonId` int(10) NOT NULL,
                `type` varchar(255) NOT NULL,
                `term` varchar(255) NOT NULL COMMENT '学期代码',
                `userId` int(10) NOT NULL,
                `score` int(10) NOT NULL COMMENT '评分',
                `source` varchar(255) NOT NULL COMMENT '来源',
                `remark` text COMMENT '描述',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课次评价';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table czie_teacher_score');
    }
}
