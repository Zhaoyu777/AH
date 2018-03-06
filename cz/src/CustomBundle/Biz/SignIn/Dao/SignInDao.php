<?php

namespace CustomBundle\Biz\SignIn\Dao;

interface SignInDao
{
    public function getByLessonIdAndTime($lessonId, $time);

    public function findByLessonId($lessonId);

    public function findByCourseId($courseId);
}
