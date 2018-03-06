<?php

use Phpmig\Migration\Migration;

class CzieLessonEvaluation extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `czie_lesson_evaluation` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `courseId` int(10) NOT NULL COMMENT '课程id',
                `lessonId` int(10) NOT NULL COMMENT '课次id',
                `remark` text COMMENT '评论内容',
                `score` int(10) NOT NULL COMMENT '评分',
                `studentId` int(10) NOT NULL COMMENT '学员id',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
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
        $biz['db']->exec('drop table czie_lesson_evaluation');
    }
}
