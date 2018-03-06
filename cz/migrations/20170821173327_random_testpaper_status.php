<?php

use Phpmig\Migration\Migration;

class RandomTestpaperStatus extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->isFieldExist('random_testpaper_item', 'status')) {
            $biz['db']->exec("
                ALTER TABLE `random_testpaper_item` ADD `status` varchar(10) DEFAULT 'noAnswer';
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('random_testpaper_item', 'status')) {
            $biz['db']->exec('
                ALTER TABLE `random_testpaper_item` DROP COLUMN `status`;
            ');
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
