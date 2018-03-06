<?php

use Phpmig\Migration\Migration;

class AddTaskResultColumns extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
           alter table activity_display_wall_result add column  `memberCount` int(10) DEFAULT 1;
           alter table activity_brain_storm_result add column  `memberCount` int(10) DEFAULT 1;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
