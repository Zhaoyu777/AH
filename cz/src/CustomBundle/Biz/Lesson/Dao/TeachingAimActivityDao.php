<?php

namespace CustomBundle\Biz\Lesson\Dao;

interface TeachingAimActivityDao
{
    public function findRelationsByActivityId($activityId);

    public function findRelationsByCourseIdAndTermCode($courseId, $termCode);
    
    public function findRelationsByLessonIdAndTermCode($lessonId, $termCode);

    public function findRelationsByOrgCodeAndTermCode($orgCode, $termCode);

    public function countRelationsByCourseIdsAndTeacherIdAndTermCode($courseIds, $teacherId, $termCode);

    public function deleteRelationsByActivityId($activityId);

    public function deleteRelationsByAimId($aimId);
}
