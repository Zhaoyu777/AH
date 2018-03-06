<?php

namespace CustomBundle\Biz\Score\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Score\Service\ScoreService;

class ScoreServiceImpl extends BaseService implements ScoreService
{
    public function createScore($score)
    {
        if (!ArrayToolkit::requireds($score, array('courseId', 'term', 'userId', 'targetType', 'targetId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $score = ArrayToolkit::parts($score, array(
            'courseId',
            'term',
            'userId',
            'score',
            'type',
            'lessonId',
            'targetType',
            'targetId',
            'remark',
            'taskId',
        ));

        $this->beginTransaction();
        try {
            $created = $this->getScoreDao()->create($score);

            $this->commit();

            $this->dispatchEvent('score.create', new Event($created));

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getScoreByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getScoreDao()->getByTaskIdAndUserId($taskId, $userId);
    }

    public function deleteScoresByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return null;
        }

        return $this->getScoreDao()->deleteByTaskIds($taskIds);
    }

    public function deleteScoreByTargetTypeAndTargetId($targetType, $targetId)
    {
        return $this->getScoreDao()->deleteByTargetTypeAndTargetId($targetType, $targetId);
    }

    public function countScores($conditions)
    {
        return $this->getScoreDao()->count($conditions);
    }

    public function findScoresByUserId($userId)
    {
        return $this->getScoreDao()->findByUserId($userId);
    }

    public function findScoresByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->getScoreDao()->findByLessonIdAndUserId($lessonId, $userId);
    }

    public function findScoresByLessonId($lessonId, $start, $limit)
    {
        return $this->getScoreDao()->findByLessonId($lessonId, $start, $limit);
    }

    public function findByTermAndUserId($term, $userId, $start = 0, $limit = PHP_INT_MAX)
    {
        return $this->getScoreDao()->findByTermAndUserId($term, $userId, $start, $limit);
    }

    public function findUserSumScoresByCourseId($courseId)
    {
        return ArrayToolkit::index($this->getScoreDao()->findUserSumScoresByCourseId($courseId), 'userId');
    }

    public function countUserByLessonId($lessonId)
    {
        return $this->getScoreDao()->countUserByLessonId($lessonId);
    }

    public function searchScores($conditions, $orderBy, $start, $limit)
    {
        return $this->getScoreDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function sumScoresByLessonId($lessonId)
    {
        return $this->getScoreDao()->sumScoresByLessonId($lessonId);
    }

    public function findUserSumScoresByLessonId($lessonId)
    {
        return $this->getScoreDao()->findUserSumScoresByLessonId($lessonId);
    }

    public function sumScoresByTermAndUserId($term, $userId)
    {
        return $this->getScoreDao()->sumByTermAndUserId($term, $userId);
    }

    public function calculateStudentsScoresByCourseId($courseId)
    {
        $results = $this->getScoreDao()->sumStudentsScoresByCourseId($courseId);
        $results = ArrayToolkit::index($results, 'userId');

        foreach ($results as &$result) {
            $result = $result['score'];
        }

        return $results;
    }

    public function countStudentsScoresByUserIdAndLessonIds($userId, $lessonIds)
    {
        if (empty($lessonIds)) {
            return 0;
        }

        return $this->getScoreDao()->countStudentsScoresByUserIdAndLessonIds($userId, $lessonIds);
    }

    protected function getScoreDao()
    {
        return $this->createDao('CustomBundle:Score:ScoreDao');
    }
}
