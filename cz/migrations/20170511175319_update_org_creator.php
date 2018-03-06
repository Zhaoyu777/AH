<?php

use Phpmig\Migration\Migration;

class UpdateOrgCreator extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
          UPDATE org SET createdUserId = 2;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
