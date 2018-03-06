<?php

namespace CustomBundle\Biz\Course\Dao;

interface ChapterDao
{
    public function findByLessonId($lessonId);
}
