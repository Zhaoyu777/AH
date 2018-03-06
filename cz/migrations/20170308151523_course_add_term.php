<?php

use Phpmig\Migration\Migration;

class CourseAddTerm extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("ALTER TABLE `c2_course` ADD `term_code` VARCHAR(255);");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('ALTER TABLE `c2_course` DROP COLUMN `term_code`');
    }
}
