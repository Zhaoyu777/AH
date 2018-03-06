<?php

use Phpmig\Migration\Migration;

class SuperAdminAddPermission extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        $data = $biz['db']->fetchAssoc("SELECT data FROM `role` WHERE code = 'ROLE_SUPER_ADMIN'");
        $data = json_decode($data['data']);
        $data[] = 'admin_data_board';
        $data = json_encode($data);

        $biz['db']->exec("UPDATE `role` SET data = '{$data}' WHERE code = 'ROLE_SUPER_ADMIN'");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
