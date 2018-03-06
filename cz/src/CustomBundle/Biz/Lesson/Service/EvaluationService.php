<?php

namespace CustomBundle\Biz\Lesson\Service;

interface EvaluationService
{
    public function tryEvaluate($evaluation);

    public function createEvaluation($evaluation);

    public function findEvaluationsByCourseIdAndUserId($courseId, $userId);

    public function getEvaluationByLessonIdAndUserId($lessonId, $userId);

    public function findEvaluationsByCourseId($courseId);

    public function getEvaluation($id);

    public function getScoreAvgByLessonId($lessonId);
}
