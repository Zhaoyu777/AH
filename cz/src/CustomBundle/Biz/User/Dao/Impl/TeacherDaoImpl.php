<?php

namespace CustomBundle\Biz\User\Dao\Impl;

use CustomBundle\Biz\User\Dao\TeacherDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class TeacherDaoImpl extends GeneralDaoImpl implements TeacherDao
{
    protected $table = 'czie_teacher';

    public function getByCode($code)
    {
        return $this->getByFields(array('zgh' => $code));
    }

    public function searchAnalysisTeachers($conditions, $start, $limit)
    {
        $sql = "SELECT * FROM
                (SELECT u.id userId, count(c.id) courseCount,u.loginTime loginTime FROM `czie_api_course` c LEFT JOIN `user` u ON c.jsdm = u.nickname LEFT JOIN user_profile p on u.id = p.id WHERE c.xq = ? AND u.orgCode LIKE ?";
        $fields = array(
            $conditions['termCode'],
            "%{$conditions['orgCode']}%"
        );

        if (!empty($conditions['queryField'])) {
            $sql .= " AND (u.nickname LIKE ? OR p.truename LIKE ?)";
            $fields[] = "%".$conditions['queryField']."%";
            $fields[] = "%".$conditions['queryField']."%";
        }

        $sql .= " GROUP BY u.id) g where g.courseCount > 0 ORDER BY g.loginTime DESC LIMIT {$start}, {$limit}";

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function countAnalysisTeachers($conditions)
    {
        $sql = "SELECT count(g.userId) FROM
                (SELECT u.id userId, count(c.id) courseCount,u.loginTime loginTime FROM `czie_api_course` c LEFT JOIN `user` u ON c.jsdm = u.nickname LEFT JOIN user_profile p on u.id = p.id WHERE c.xq = ? AND u.orgCode LIKE ?";
        $fields = array(
            $conditions['termCode'],
            "%{$conditions['orgCode']}%"
        );

        if (!empty($conditions['queryField'])) {
            $sql .= " AND (u.nickname LIKE ? OR p.truename LIKE ?)";
            $fields[] = "%".$conditions['queryField']."%";
            $fields[] = "%".$conditions['queryField']."%";
        }

        $sql .= " GROUP BY u.id) g where g.courseCount > 0";

        return $this->db()->fetchColumn($sql, $fields) ?: 0;
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
