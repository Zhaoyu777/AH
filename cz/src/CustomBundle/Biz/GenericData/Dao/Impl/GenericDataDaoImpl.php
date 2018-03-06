<?php

namespace CustomBundle\Biz\GenericData\Dao\Impl;

use CustomBundle\Biz\GenericData\Dao\GenericDataDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class GenericDataDaoImpl extends GeneralDaoImpl implements GenericDataDao
{
    protected $table = 'generic_data';

    public function getDataByType($type)
    {
        return $this->getByFields(array('type' => $type));
    }

    public function declares()
    {
        return array(
            'serializes' => array('data' => 'json'),
            'orderbys'   => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array()
        );
    }
}
