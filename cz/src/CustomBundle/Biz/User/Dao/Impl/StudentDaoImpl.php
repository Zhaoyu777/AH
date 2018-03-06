<?php

namespace CustomBundle\Biz\User\Dao\Impl;

use CustomBundle\Biz\User\Dao\StudentDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class StudentDaoImpl extends GeneralDaoImpl implements StudentDao
{
    protected $table = 'czie_student';

    public function findByUserIds($userIds)
    {
        return $this->findInField('userId', $userIds);
    }

    public function getByCode($code)
    {
        return $this->getByFields(array('xh' => $code));
    }

    public function findNotRegister()
    {
        $sql = "SELECT * FROM {$this->table} WHERE userId is null LIMIT 1000;";
        return $this->db()->fetchAll($sql) ?: array();
    }

    public function countNotRegister()
    {
        $sql = "SELECT count(*) FROM {$this->table} WHERE userId is null LIMIT 1;";
        return $this->db()->fetchColumn($sql);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'yxdm = :orgCode',
                'xm LIKE :queryField',
            )
        );
    }
}
