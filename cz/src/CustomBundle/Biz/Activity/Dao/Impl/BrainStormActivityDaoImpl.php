<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\BrainStormActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class BrainStormActivityDaoImpl extends GeneralDaoImpl implements BrainStormActivityDao
{
    protected $table = 'activity_brain_storm';

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
