<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\RandomTestpaperActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RandomTestpaperActivityDaoImpl extends GeneralDaoImpl implements RandomTestpaperActivityDao
{
    protected $table = 'activity_random_testpaper';

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function declares()
    {
        return array(
            'serializes' => array('metas' => 'json'),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
