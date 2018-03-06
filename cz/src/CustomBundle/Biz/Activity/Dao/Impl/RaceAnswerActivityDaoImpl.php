<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\RaceAnswerActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RaceAnswerActivityDaoImpl extends GeneralDaoImpl implements RaceAnswerActivityDao
{
    protected $table = 'activity_race_answer';

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
