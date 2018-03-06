<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use Codeages\Biz\Framework\Dao\GeneralDaoImpl;
use CustomBundle\Biz\Course\Dao\CzieApiCourseSetDao;

class CzieApiCourseSetDaoImpl extends GeneralDaoImpl implements CzieApiCourseSetDao
{
    protected $table = 'czie_api_course_set';

    public function getByKcdm($code)
    {
        return $this->getByFields(array('kcdm' => $code));
    }

    public function getByCourseSetId($courseSetId)
    {
        return $this->getByFields(array('courseSetId' => $courseSetId));
    }

    public function findByCourseSetIds($courseSetIds)
    {
        return $this->findInField('courseSetId', $courseSetIds);
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
