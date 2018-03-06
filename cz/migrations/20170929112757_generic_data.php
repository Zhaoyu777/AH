<?php

use Phpmig\Migration\Migration;

class GenericData extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $biz['db']->exec("
            CREATE TABLE `generic_data` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `userId` int(10) NOT NULL DEFAULT 0 COMMENT '该数据生成人的id',
                `type` varchar(64) NOT NULL COMMENT '该数据用于的具体类型',
                `data` text NOT NULL COMMENT '该内容的具体数据',
                `times` int(10) NOT NULL DEFAULT 0 COMMENT '该数据可使用的次数(0表示没有限制)',
                `remainedTimes` int(10) NOT NULL DEFAULT 0 COMMENT '剩余使用次数',
                `expiredTime` int(10) NOT NULL DEFAULT 0 COMMENT '数据过期时间',
                `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table generic_data');
    }
}
