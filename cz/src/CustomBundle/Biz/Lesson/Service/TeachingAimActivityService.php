<?php 

namespace CustomBundle\Biz\Lesson\Service;

interface TeachingAimActivityService 
{
    const CONNECTED_AIMS_MAX = 20;

    public function findByActivityId($activityId);

    public function batchCreate($relations);

    public function connectAims($activityId, $lessonId, $aimIds);

    public function calcCourseFinishedRate($courseId);

    public function calcLessonFinishedRate($lessonId);

    public function calcTeacherFinishedRate($teacherId);

    public function calcCollegeFinishedRate($orgCode);

    public function deleteByActivityId($activityId);

    public function processRelations($relations);

    public function processRelation($relation);
}