<?php

use Phpmig\Migration\Migration;

class AddPracticeWorkResultFrom extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `practice_work_result` ADD `origin` varchar(64) NOT NULL DEFAULT 'pc' AFTER `id`");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `practice_work_result` DROP COLUMN `origin` ");
    }
}
