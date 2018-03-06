<?php

use Phpmig\Migration\Migration;

class RandomTestpaperLessonIdDefault extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        $biz['db']->exec("ALTER TABLE `random_testpaper` modify column `lessonId` int(10) DEFAULT 0 COMMENT '课次Id'");

        if (!$this->isFieldExist('random_testpaper', 'passedScore')) {
            $biz['db']->exec("
                ALTER TABLE `random_testpaper` ADD `passedScore` float(10,1) DEFAULT '0.0' AFTER `doTime`;
            ");
        }

        if (!$this->isFieldExist('random_testpaper', 'status')) {
            $biz['db']->exec("
                ALTER TABLE `random_testpaper` ADD `status` varchar(10) DEFAULT 'unpassed' AFTER `score`;
            ");
        }

        if (!$this->isFieldExist('random_testpaper_item', 'score')) {
            $biz['db']->exec("
                ALTER TABLE `random_testpaper_item` ADD `score` float(10,1) DEFAULT '0.0' AFTER `seq`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('random_testpaper', 'passedScore')) {
            $biz['db']->exec('
                ALTER TABLE `random_testpaper` DROP COLUMN `passedScore`;
            ');
        }

        if ($this->isFieldExist('random_testpaper', 'status')) {
            $biz['db']->exec('
                ALTER TABLE `random_testpaper` DROP COLUMN `status`;
            ');
        }

        if ($this->isFieldExist('random_testpaper_item', 'score')) {
            $biz['db']->exec('
                ALTER TABLE `random_testpaper_item` DROP COLUMN `score`;
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
