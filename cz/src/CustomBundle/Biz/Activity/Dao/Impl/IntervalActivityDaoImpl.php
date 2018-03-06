<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\IntervalActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class IntervalActivityDaoImpl extends GeneralDaoImpl implements IntervalActivityDao
{
    protected $table = 'activity_interval';

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
