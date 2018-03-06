<?php

use Phpmig\Migration\Migration;

class AddTaskStatusCostTime extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
           alter table czie_task_status add column `costTime` int(10) DEFAULT 0;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
