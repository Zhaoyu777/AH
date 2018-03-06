<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\RollcallActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RollcallActivityDaoImpl extends GeneralDaoImpl implements RollcallActivityDao
{
    protected $table = 'activity_rollcall';

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
