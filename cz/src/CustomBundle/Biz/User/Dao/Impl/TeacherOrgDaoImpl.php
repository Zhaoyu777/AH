<?php

namespace CustomBundle\Biz\User\Dao\Impl;

use CustomBundle\Biz\User\Dao\TeacherOrgDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class TeacherOrgDaoImpl extends GeneralDaoImpl implements TeacherOrgDao
{
    protected $table = 'czie_api_teacher_org';

    public function getByCode($code)
    {
        return $this->getByFields(array('department_key' => $code));
    }

    public function findByParentCode($code)
    {
        return $this->findByFields(array(
            'parent_key' => $code
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
