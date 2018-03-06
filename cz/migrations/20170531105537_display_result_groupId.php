<?php

use Phpmig\Migration\Migration;

class DisplayResultGroupId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('activity_display_wall_result', 'groupId')) {
            $biz['db']->exec("
                ALTER TABLE activity_display_wall_result ADD `groupId` int(11) NOT NULL DEFAULT 0 AFTER `courseTaskId`;

                UPDATE activity_display_wall SET submitWay = 'person' WHERE groupWay = 'none';
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('activity_display_wall_result', 'groupId')) {
            $biz['db']->exec("
                ALTER TABLE `activity_display_wall_result` DROP COLUMN `groupId`;
            ");
        }
    }

    protected function isFieldExist($table, $filedName)
    {
        $biz = $this->getContainer();

        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
