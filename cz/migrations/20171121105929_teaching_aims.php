<?php

use Phpmig\Migration\Migration;

class TeachingAims extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `zhkt_lesson_teaching_aims` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                `courseId` int(11) NOT NULL default '0' COMMENT '课程ID',
                `orgCode`  varchar(255) DEFAULT '' COMMENT '学院代码',
                `lessonId` int(11) NOT NULL default '0' COMMENT '课次ID',
                `parentId` int(11) NOT NULL default '0' COMMENT '父ID',
                `number` int(11) NOT NULL default '0' COMMENT '顺序标号',
                `type` enum('abilityAim','knowledgeAim','qualityAim') NOT NULL COMMENT '目标类型 ability：能力，knowledge：知识，quality：素质',
                `content` text COMMENT '目标内容',
                `termCode`  varchar(255) NOT NULL DEFAULT '' COMMENT '学期代码',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',   
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='教案目标表';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table zhkt_lesson_teaching_aims');
    }
}
