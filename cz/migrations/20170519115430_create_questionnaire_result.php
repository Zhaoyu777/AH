<?php

use Phpmig\Migration\Migration;

class CreateQuestionnaireResult extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `questionnaire_result` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `questionnaireId` int(10) NOT NULL COMMENT '调查问卷id',
              `userId` int(10) NOT NULL COMMENT '做调查问卷的人',
              `taskId` int(10) NOT NULL COMMENT '所属任务',
              `activityId` int(10) NOT NULL COMMENT '所属活动',
              `status` varchar(255) NOT NULL COMMENT '状态',
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
        
        $biz['db']->exec('DROP TABLE IF EXISTS `questionnaire_result`');
    }
}
