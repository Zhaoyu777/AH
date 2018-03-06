<?php

namespace CustomBundle\Biz\Api\Dao\Impl;

use CustomBundle\Biz\Api\Dao\SyncJobDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class SyncJobDaoImpl extends GeneralDaoImpl implements SyncJobDao
{
    protected $table = 'czie_sync_job';

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }

    public function getLastJob()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY createdTime DESC LIMIT 1;";

        return $this->db()->fetchAssoc($sql) ?: null;
    }
}
