<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\OneSentenceActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class OneSentenceActivityDaoImpl extends GeneralDaoImpl implements OneSentenceActivityDao
{
    protected $table = 'activity_one_sentence';

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
