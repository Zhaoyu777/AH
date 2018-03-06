<?php

namespace CustomBundle\Biz\User\Dao\Impl;

use CustomBundle\Biz\User\Dao\MajorDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class MajorDaoImpl extends GeneralDaoImpl implements MajorDao
{
    protected $table = 'czie_major';

    public function getByCode($code)
    {
        return $this->getByFields(array('code' => $code));
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
