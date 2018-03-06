<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\ChapterDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ChapterDaoImpl extends GeneralDaoImpl implements ChapterDao
{
    protected $table = 'czie_course_lesson_chapter';

    public function findByLessonId($lessonId)
    {
        return $this->findByFields(array('lessonId' => $lessonId));
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
