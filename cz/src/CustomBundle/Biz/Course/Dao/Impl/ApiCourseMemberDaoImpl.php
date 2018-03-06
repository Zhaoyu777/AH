<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\ApiCourseMemberDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ApiCourseMemberDaoImpl extends GeneralDaoImpl implements ApiCourseMemberDao
{
    protected $table = 'czie_api_course_member';

    public function getByCourseIdAndNo($courseId, $no)
    {
        return $this->getByFields(array(
            'courseId' => $courseId,
            'xh'       => $no
        ));
    }

    public function findByMemberIds($memberIds)
    {
        return $this->findInField('memberId', $memberIds);
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
