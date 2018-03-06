<?php

namespace CustomBundle\Biz\Org\Dao\Impl;

use CustomBundle\Biz\Org\Dao\LeaderDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class LeaderDaoImpl extends GeneralDaoImpl implements LeaderDao
{
    protected $table = 'zhkt_faculty_leader';

    public function findByOrgId($orgId)
    {
        return $this->findByFields(array('orgId' => $orgId));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array(),
        );
    }
}
