<?php

namespace CustomBundle\Biz\Score\Service;

interface ScoreService
{
    public function createScore($score);

    public function findScoresByUserId($userId);

    public function findByTermAndUserId($term, $userId, $start, $limit);

    public function findUserSumScoresByCourseId($courseId);

    public function calculateStudentsScoresByCourseId($courseId);
}
