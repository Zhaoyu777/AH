<?php

namespace CustomBundle\Biz\Course\Dao;

interface LessonTaskDao
{
    public function getByTaskId($taskId);

    public function getFirstInClassByLessonId($lessonId);

    public function countByLessonIds($lessonIds);

    public function findByLessonId($lessonId);

    public function findTasksByLessonIdAndStage($lessonId, $stage);

    public function findByCourseId($courseId);

    public function findTeachedLessonTasksByCourseId($courseId);

    public function findByTaskIds($taskIds);

    public function findInByTaskIds($taskIds);

    public function findSchoolTasksByTermCode($termCode);
}