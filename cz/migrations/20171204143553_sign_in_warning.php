<?php

use Phpmig\Migration\Migration;

class SignInWarning extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE IF NOT EXISTS `zhkt_sign_in_warning` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `userId` int(10) NOT NULL,
                `keepAttendTimes` int(10) NOT NULL DEFAULT '0',
                `keepAbsentTimes` int(10) NOT NULL DEFAULT '0',
                `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='学生签到预警表';
        ");

        $biz['db']->exec('INSERT INTO `zhkt_sign_in_warning` (userId) SELECT id FROM `user`');
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table zhkt_sign_in_warning');
    }
}
