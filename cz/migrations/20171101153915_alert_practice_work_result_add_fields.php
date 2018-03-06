<?php

use Phpmig\Migration\Migration;

class AlertPracticeWorkResultAddFields extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            ALTER TABLE `practice_work_result` Add column reviewTime int(10) unsigned NOT NULL DEFAULT '0' COMMENT '批阅时间' AFTER `updatedTime`;
            ALTER TABLE `practice_work_result` Add column finalSubTime int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后一次提交时间' AFTER `reviewTime`;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
