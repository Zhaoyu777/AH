<?php

namespace CustomBundle\Biz\Lesson\Dao;

interface TeachingAimDao
{
    public function getAimByParentIdAndLessonId($parentId, $lessonId);

    public function findAimsByLessonId($lessonId);

    public function findAimsByAimIds($aimIds);

    public function findAimsByParentIds($aimIds);

    public function findUniqueCourseIds($courseIds);

    public function findAimsByCourseIdAndTermCode($courseId, $termCode);

    public function findAimsByLessonIdAndTermCode($lessonId, $termCode);

    public function findAimsByOrgCodeAndTermCode($orgCode, $termCode);

    public function countCourseOwnedAimsByCourseIdsAndTermCode($courseIds, $termCode);

    public function deleteAimsByLessonId($lessonId);
}
