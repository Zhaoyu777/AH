<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use Codeages\Biz\Framework\Dao\GeneralDaoImpl;
use CustomBundle\Biz\Course\Dao\CourseWarningDao;

class CourseWarningDaoImpl extends GeneralDaoImpl implements CourseWarningDao
{
    protected $table = 'zhkt_course_continuous_warning';

    public function declares()
    {
        return array(
            'conditions' => array(
                'continuous >= :gtContinuous'
            ),
            'serializes' => array(),
            'orderbys'  => array(),
            'timestamps' => array()
        );
    }
}
