<?php

namespace CustomBundle\Biz\Lesson\Dao\Impl;

use CustomBundle\Biz\Lesson\Dao\RecordDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RecordDaoImpl extends GeneralDaoImpl implements RecordDao
{
    protected $table = 'teaching_lesson_record';

    public function getRecordByLessonId($lessonId)
    {
        return $this->getByFields(array(
            'lessonId' => $lessonId
        ));
    }

    public function deleteByLesonId($lessonId)
    {
        $sql = "DELETE FROM {$this->table} WHERE lessonId = ? ";

        return $this->db()->executeUpdate($sql, array($lessonId));
    }

    public function getByCourseId($courseId)
    {
        return $this->getByFields(array(
            'courseId' => $courseId
        ));
    }

    public function declares()
    {
        return array(
            'timestamps' => array(
                'createdTime',
                'updatedTime'
            ),
        );
    }
}
