<?php

use Phpmig\Migration\Migration;

class CreateQuestionnaireQuestion extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `questionnaire_question` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '题目ID',
              `type` varchar(64) NOT NULL DEFAULT '' COMMENT '题目类型',
              `stem` text COMMENT '题干',
              `metas` text COMMENT '题目元信息',
              `questionnaireId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属调查问卷',
              `seq` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='调查问卷问题表';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        
        $biz['db']->exec('DROP TABLE IF EXISTS `questionnaire_question`');
    }
}
