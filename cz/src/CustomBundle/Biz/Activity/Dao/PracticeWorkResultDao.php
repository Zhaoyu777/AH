<?php

namespace CustomBundle\Biz\Activity\Dao;

interface PracticeWorkResultDao
{
    public function getByTaskIdAndUserId($taskId, $userId);

    public function findResultByPracticeWorkIds($practiceWorkIds);

    public function findResultsStatusNumGroupByStatus($practiceWorkId);

    public function getLastResultByPracticeWorkId($practiceWorkId);

    public function findPracticeWorkResultsByPracticeWorkId($practiceWorkId);

    public function findByTaskIds($taskIds);
}
