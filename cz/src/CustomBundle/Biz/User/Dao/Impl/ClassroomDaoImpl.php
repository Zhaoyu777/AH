<?php

namespace CustomBundle\Biz\User\Dao\Impl;

use CustomBundle\Biz\User\Dao\ClassroomDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ClassroomDaoImpl extends GeneralDaoImpl implements ClassroomDao
{
    protected $table = 'czie_classroom';

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
