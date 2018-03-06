<?php

use Phpmig\Migration\Migration;

class RandomTestpaperQuestionType extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->isFieldExist('random_testpaper_item', 'questionType')) {
            $biz['db']->exec("
                ALTER TABLE `random_testpaper_item` ADD `questionType` varchar(255) AFTER `answer`;
            ");
        }

        if (!$this->isFieldExist('random_testpaper_item', 'missScore')) {
            $biz['db']->exec("
                ALTER TABLE `random_testpaper_item` ADD `missScore` float(10,1) DEFAULT '0.0' AFTER `answer`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('random_testpaper_item', 'questionType')) {
            $biz['db']->exec('
                ALTER TABLE `random_testpaper_item` DROP COLUMN `questionType`;
            ');
        }

        if ($this->isFieldExist('random_testpaper_item', 'missScore')) {
            $biz['db']->exec('
                ALTER TABLE `random_testpaper_item` DROP COLUMN `missScore`;
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
