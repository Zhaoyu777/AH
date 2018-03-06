<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\PracticeWorkActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class PracticeWorkActivityDaoImpl extends GeneralDaoImpl implements PracticeWorkActivityDao
{
    protected $table = 'activity_practice_work';

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
