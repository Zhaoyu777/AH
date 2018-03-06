<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\GroupDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class GroupDaoImpl extends GeneralDaoImpl implements GroupDao
{
    protected $table = 'czie_student_group';

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId));
    }

    public function getDefaultByCourseId($courseId)
    {
        return $this->getByFields(array(
            'courseId' => $courseId,
            'type' => 'default',
        ));
    }

    public function getMaxGroupNumberByCourseId($courseId)
    {
        $sql = "SELECT MAX(number) FROM {$this->table()} WHERE  courseId = ?";

        return $this->db()->fetchColumn($sql, array($courseId));
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
