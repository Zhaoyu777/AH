<?php

use Phpmig\Migration\Migration;

class AlterScoreIndex extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec('ALTER TABLE `czie_user_score` ADD INDEX userId ( `userId`);');
        $connection->exec('ALTER TABLE `czie_user_score` ADD INDEX term ( `term`);');

        $connection->exec('ALTER TABLE `czie_teacher_score` ADD INDEX userId ( `userId`);');
        $connection->exec('ALTER TABLE `czie_teacher_score` ADD INDEX term ( `term`);');
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec('ALTER TABLE `czie_user_score` DROP INDEX userId;');
        $connection->exec('ALTER TABLE `czie_user_score` DROP INDEX term;');

        $connection->exec('ALTER TABLE `czie_teacher_score` DROP INDEX userId;');
        $connection->exec('ALTER TABLE `czie_teacher_score` DROP INDEX term;');
    }
}
