<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\ApiCourseDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ApiCourseDaoImpl extends GeneralDaoImpl implements ApiCourseDao
{
    protected $table = 'czie_api_course';

    public function getByParam($xq, $kcdm, $hb, $jsdm, $zjjs, $skbj, $lx, $lbdm)
    {
        return $this->getByFields(array(
            'xq'   => $xq,
            'kcdm' => $kcdm,
            'hb'   => $hb,
            'jsdm' => $jsdm,
            'zjjs' => $zjjs,
            'skbj' => $skbj,
            'lx'   => $lx,
            'lbdm' => $lbdm
        ));
    }

    public function findNoMakeByTerm($term)
    {
        $sql = "SELECT distinct xq,kcdm,hb,kch,kcmc,xs,skbj,lx,lbdm FROM {$this->table} WHERE jsdm is not null and jsdm != '' and courseId is null and xq = ? LIMIT 100;";

        return $this->db()->fetchAll($sql, array($term));
    }

    public function findByParam($xq, $kcdm, $hb, $skbj, $lx, $lbdm)
    {
        $sql = "SELECT * FROM {$this->table} WHERE jsdm is not null and jsdm != '' and xq = ? and kcdm = ? and hb = ? and skbj = ? and lx =? and lbdm = ? order by zjjs ASC;";

        return $this->db()->fetchAll($sql, array($xq, $kcdm, $hb, $skbj, $lx, $lbdm));
    }

    public function getAlreadyMake($xq, $kcdm, $hb, $skbj, $lx, $lbdm)
    {
        $sql = "SELECT * FROM {$this->table} WHERE courseId is not null and xq = ? and kcdm = ? and hb = ? and skbj = ? and lx =? and lbdm = ? LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($xq, $kcdm, $hb, $skbj, $lx, $lbdm)) ?: null;
    }

    public function findMasterTeachersByCourseId($courseId)
    {
        return $this->findByFields(array(
            'courseId' => $courseId,
            'zjjs' => 1,
        ));
    }

    public function findAssistantTeachersByCourseId($courseId)
    {
        return $this->findByFields(array(
            'courseId' => $courseId,
            'zjjs' => 2,
        ));
    }

    public function countAllTeachersByOrgCodeAndTermCode($orgCode, $termCode)
    {
        $orgCode = $orgCode.'%';
        $sql = "SELECT COUNT(*) from (SELECT ac.jsdm FROM {$this->table} ac LEFT JOIN `user` u ON ac.jsdm = u.nickname WHERE u.orgCode LIKE ? AND ac.xq = ? AND ac.jsdm is not null GROUP BY ac.jsdm) t";

        return $this->db()->fetchColumn($sql, array($orgCode, $termCode));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array('zjjs'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array('courseId = :courseId')
        );
    }
}
