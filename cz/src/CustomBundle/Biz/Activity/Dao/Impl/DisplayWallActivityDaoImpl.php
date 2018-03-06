<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\DisplayWallActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class DisplayWallActivityDaoImpl extends GeneralDaoImpl implements DisplayWallActivityDao
{
    protected $table = 'activity_display_wall';

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
