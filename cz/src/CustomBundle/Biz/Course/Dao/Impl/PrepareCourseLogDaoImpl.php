<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\PrepareCourseLogDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class PrepareCourseLogDaoImpl extends GeneralDaoImpl implements PrepareCourseLogDao
{
    protected $table = 'czie_prepare_course_log';

    public function getByCourseId($courseId)
    {
        return $this->getByFields(array('courseId' => $courseId));
    }

    public function countByTermCodeAndOrgCode($termCode, $orgCode)
    {
        $orgCode = "%{$orgCode}%";

        $sql = "SELECT count(cl.id) FROM {$this->table} cl LEFT JOIN czie_api_course c ON cl.courseId = c.courseId LEFT JOIN user u ON c.jsdm = u.nickname WHERE cl.termCode = ? AND u.orgCode LIKE ?";

        return $this->db()->fetchColumn($sql, array($termCode, $orgCode)) ? :0;
    }

    public function countCurrentTermTeachersByOrgCode($termCode, $orgCode)
    {
        $orgCode = "{$orgCode}%";

        $sql = "SELECT COUNT(*) from (SELECT pc.userId FROM {$this->table} pc LEFT JOIN `user` u ON pc.userId = u.id LEFT JOIN `course_v8` c ON c.id = pc.courseId LEFT JOIN `course_set_v8` cs ON cs.id = c.courseSetId WHERE u.orgCode LIKE ? AND pc.termCode = ? AND cs.courseNo is not null GROUP BY pc.userId) t";

        return $this->db()->fetchColumn($sql, array($orgCode, $termCode)) ? :0;
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'termCode = :termCode',
            )
        );
    }
}
