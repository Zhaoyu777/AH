<?php

namespace CustomBundle\Biz\Testpaper\Service\Impl;

use Biz\Activity\Type\Testpaper;
use AppBundle\Common\ArrayToolkit;
use Biz\Testpaper\Service\Impl\TestpaperServiceImpl as BaseService;

class TestpaperServiceImpl extends BaseService
{
    public function getResult($id)
    {
        return $this->getResultDao()->get($id);
    }

    public function findTestpaperResultsByActivityIds($activityIds)
    {
        return $this->getResultDao()->findByActivityIds($activityIds);
    }

    public function countResultByTestId($testId)
    {
        return $this->getResultDao()->countByTestId($testId);
    }

    public function deleteTestpaperResultByTaskIds($taskIds)
    {
        $tasks = $this->getTaskService()->findTasksByIds($taskIds);

        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $results = $this->findTestpaperResultsByActivityIds($activityIds);

        foreach ($results as $key => $result) {
            $this->deleteTestpaperResult($result['id']);
        }
    }

    public function findItemResultsByTestId($testId)
    {
        return $this->getItemResultDao()->findItemResultsByTestId($testId);
    }

    public function countOnlineTestpaperResults($conditions)
    {
        return $this->getResultDao()->countOnlineTestpaperResults($conditions);
    }

    public function searchOnlineTestpaperResults($conditions, $start, $limit)
    {
        return $this->getResultDao()->searchOnlineTestpaperResults($conditions, $start, $limit);
    }

    public function findItemResultsByResultIds($resultIds)
    {
        return $this->getItemResultDao()->findItemResultsByResultIds($resultIds);
    }
    public function findItemResultsByTestIdAndLessonId($testId, $lessonId)
    {
        return $this->getResultDao()->findItemResultsByTestIdAndLessonId($testId, $lessonId);
    }

    public function deleteTestpaperResult($resultId)
    {
        $result = $this->getTestpaperResultDao()->delete($resultId);
        $this->deleteTestpaperResultItems($resultId);

        return $result;
    }

    public function deleteTestpaperResultItems($resultId)
    {
        return $this->getItemResultDao()->deleteByResultId($resultId);
    }

    public function calculateStudentsAverageGradesByCourseId($courseId)
    {
        $commonTestpapers = $this->getTestpaperDao()->findByCourseId(0);
        $testpapers = $this->getTestpaperDao()->findByCourseId($courseId);
        $testpapers = array_merge($testpapers, $commonTestpapers);
        $testpapers = ArrayToolkit::index($testpapers, 'id');

        $testpaperResults = $this->getResultDao()->findByCourseId($courseId);
        $testpaperResults = ArrayToolkit::group($testpaperResults, 'userId');

        $averageGrades = array();
        foreach ($testpaperResults as $key => $testpaperResult) {
            $grades = 0;
            foreach ($testpaperResult as $value) {
                if (empty($testpapers[$value['testId']]) || $testpapers[$value['testId']]['score'] == 0) {
                    $grades += 0;
                    continue;
                }

                $grades += $value['score'] / $testpapers[$value['testId']]['score'];
            }
            if (count($testpaperResult) == 0) {
                $averageGrades[$key] = 0;
                continue;
            }
            $averageGrades[$key] = round(($grades / count($testpaperResult)) * 100, 1);
        }

        return $averageGrades;
    }

    public function searchTestpaperResultsCountByStatus($conditions)
    {
        return $this->getResultDao()->countTestpaperResultsByStatus($conditions);
    }

    public function searchTestpaperResultsByStatus($conditions, $start, $limit)
    {
        return $this->getResultDao()->searchTestpaperResultsByStatus($conditions, $start, $limit);
    }

    public function searchTestpapersOrderByLessonNumAndTaskId($testpaperIds, $start, $limit)
    {
        return $this->getTestpaperDao()->searchTestpapersOrderByLessonNumAndTaskId($testpaperIds, $start, $limit);
    }

    public function findResultsByCourseId($courseId)
    {
        return $this->getResultDao()->findByCourseId($courseId);
    }

    public function getLastResultByTestId($testId)
    {
        return $this->getResultDao()->getLastResultByTestId($testId);
    }

    public function findOpenTestpapersByLessonIds($lessonIds)
    {
        if (empty($lessonIds)) {
            return array();
        }

        return $this->getTestpaperDao()->findOpenTestpapersByLessonIds($lessonIds);
    }

    public function findTestpapersByCourseId($courseId)
    {
        return $this->getTestpaperDao()->findByCourseId($courseId);
    }

    public function findResultsByCourseIdAndUserId($courseId, $userId)
    {
        return $this->getResultDao()->findResultsByCourseIdAndUserIdAndStatus($courseId, $userId, 'finished');
    }

    public function getTestpaperDao()
    {
        return $this->createDao('CustomBundle:Testpaper:TestpaperDao');
    }

    protected function getActivityService()
    {
        return $this->createService('CustomBundle:Activity:ActivityService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getItemResultDao()
    {
        return $this->createDao('CustomBundle:Testpaper:TestpaperItemResultDao');
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:Testpaper:TestpaperResultDao');
    }
}
