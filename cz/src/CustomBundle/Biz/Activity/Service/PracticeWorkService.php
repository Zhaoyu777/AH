<?php

namespace CustomBundle\Biz\Activity\Service;

interface PracticeWorkService
{
    public function getResult($resultId);

    public function createResult($result);

    public function updateResult($id, $result);

    public function getResultByTaskIdAndUserId($taskId, $userId);

    public function findResultByPracticeWorkIds(array $practiceWorkIds);

    public function findResultsStatusNumGroupByStatus($practiceWorkId);

    public function searchResults(array $conditions, array $orderBy, $start, $limit);

    public function searchResultsCount(array $conditions);

    public function getLastResultByPracticeWorkId($practiceWorkId);
    
    public function findPracticeWorkResultsByPracticeWorkId($practiceWorkId);

    public function deleteResultsByTaskIds($taskIds);
}
