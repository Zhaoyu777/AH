<?php

use Phpmig\Migration\Migration;

class PracticeWorkResultAddEnum extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            ALTER TABLE practice_work_result CHANGE COLUMN status status ENUM('create', 'reviewing','finished') NOT NULL DEFAULT 'create';
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
