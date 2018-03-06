<?php

use Phpmig\Migration\Migration;

class AddTermCodeFiles extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `upload_files` ADD `termCode` varchar(255);");
        $connection->exec("UPDATE `upload_files` SET termCode = (SELECT shortCode FROM `czie_term` WHERE current = 1);");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("ALTER TABLE `upload_files` DROP `termCode`;");
    }
}
