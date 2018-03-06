<?php

use Phpmig\Migration\Migration;

class CreateQuestionnaireQuestionResult extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `questionnaire_question_result` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `questionnaireResultId` int(10) NOT NULL COMMENT '调查问卷结果id',
              `questionId` int(10) NOT NULL COMMENT '问题id',
              `answer` text COMMENT '答案',
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
        
        $biz['db']->exec('DROP TABLE IF EXISTS `questionnaire_question_result`');
    }
}
