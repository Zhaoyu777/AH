<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\ShareDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ShareDaoImpl extends GeneralDaoImpl implements ShareDao
{
    protected $table = 'czie_course_share';

    public function findByFromUserId($userId)
    {
        return $this->findByFields(array('fromUserId' => $userId));
    }

    public function findByToUserId($userId)
    {
        return $this->findByFields(array('toUserId' => $userId));
    }

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId));
    }

    public function countByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode)
    {
        $sql = "SELECT count(distinct(cs.id)) FROM {$this->table} cs ";
        $sql .= "LEFT JOIN `czie_api_course` ac ON cs.courseId = ac.courseId ";
        $sql .= "LEFT JOIN `user` u ON ac.jsdm = u.nickname WHERE ";

        $sql .= "cs.createdTime >= ? AND cs.createdTime < ? AND u.orgCode LIKE ?;";

        return $this->db()->fetchColumn($sql, array($startTime, $endTime, "{$orgCode}%")) ? : 0;
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array('courseId', 'fromUserId', 'toUserId'),
            'timestamps' => array('createdTime'),
            'conditions' => array()
        );
    }
}
