<?php

namespace CustomBundle\Biz\User\Dao\Impl;

use CustomBundle\Biz\User\Dao\FacultyDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class FacultyDaoImpl extends GeneralDaoImpl implements FacultyDao
{
    protected $table = 'czie_faculty';

    public function getByCode($code)
    {
        return $this->getByFields(array('code' => $code));
    }

    public function findAll()
    {
        $sql = "SELECT * FROM {$this->table}";

        return $this->db()->fetchAll($sql) ? : array();
    }

    public function getFacultyByName($name)
    {
        return $this->getByFields(array(
            'name' => $name
        ));
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
