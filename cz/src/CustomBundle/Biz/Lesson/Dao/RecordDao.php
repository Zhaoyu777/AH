<?php

namespace CustomBundle\Biz\Lesson\Dao;

interface RecordDao
{
    public function getRecordByLessonId($lessonId);
}
