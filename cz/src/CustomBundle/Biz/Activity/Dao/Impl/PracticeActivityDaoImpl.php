<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\PracticeActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class PracticeActivityDaoImpl extends GeneralDaoImpl implements PracticeActivityDao
{
    protected $table = 'activity_practice';

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function declares()
    {
        return array(
            'serializes' => array('fileIds' => 'json'),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
