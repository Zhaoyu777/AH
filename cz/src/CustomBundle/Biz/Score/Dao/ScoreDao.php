<?php

namespace CustomBundle\Biz\Score\Dao;

interface ScoreDao
{
    public function findByUserId($userId);

    public function findByTermAndUserId($term, $userId, $start, $limit);

    public function findUserSumScoresByCourseId($courseId);

    public function sumStudentsScoresByCourseId($courseId);
}
