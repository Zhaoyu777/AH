<?php

use Phpmig\Migration\Migration;

class ChangeDisplayWallStatus2TaskStatus extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if ($this->isTableExist('activity_display_way_status')) {
            $biz['db']->exec("
                ALTER TABLE `activity_display_way_status` RENAME TO `czie_task_status`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        if ($this->isTableExist('czie_task_status')) {
            $biz['db']->exec("
                ALTER TABLE `czie_task_status` RENAME TO `activity_display_way_status`;
            ");
        }
    }

    protected function isTableExist($table)
    {
        $biz = $this->getContainer();
        $sql = "SHOW TABLES LIKE '{$table}'";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
