<?php

namespace CustomBundle\Biz\Statistics\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Statistics\Dao\TeacherCourseStatisticsDao;
use CustomBundle\Biz\Statistics\Service\TeacherCourseStatisticsService;

class TeacherCourseStatisticsServiceImpl extends BaseService implements TeacherCourseStatisticsService
{
    public function tryStatistics($fields)
    {
    }

    public function createStatistics($fields)
    {
        $this->tryStatistics($fields);

        $user = $this->getCurrentUser();

        $created = $this->getStatisticsDao()->create($fields);

        return $created;
    }

    public function updateStatistics($id, $fields)
    {
        $this->tryStatistics($fields);

        return $this->getStatisticsDao()->update($id, $fields);
    }

    public function getStatisticsByUserId($userId)
    {
        return $this->getStatisticsDao()->getByUserId($userId);
    }

    public function getStatisticsByUserIdAndTermCode($userId, $termCode)
    {
        $statistics = $this->getStatisticsDao()->getByUserIdAndTermCode($userId, $termCode);

        return $this->statisticsCheck($statistics);
    }

    protected function statisticsCheck($statistics)
    {
        $checks = array('courseLessonRate', 'lessonRate', 'studentAttendRate', 'taskOuterCompletionRate', 'taskInCompletionRate');

        foreach ($checks as $check) {
            if (empty($statistics[$check])) {
                continue;
            }
            if ($statistics[$check] > 1) {
                $statistics[$check] = 1;
            } elseif ($statistics[$check] < 0) {
                $statistics[$check] = 0;
            }
        }

        return $statistics;
    }

    public function getStatisticsPercentageByUserIdAndTermCode($userId, $termCode)
    {
        $statistics = $this->getStatisticsByUserIdAndTermCode($userId, $termCode);

        $results = array(
            'courseLessonRate' => 0,
            'lessonRate' => 0,
            'studentAttendRate' => 0,
            'taskOuterCompletionRate' => 0,
            'taskInCompletionRate' => 0,
            'loginDays' => 0,
            'homeworkNum' => 0,
            'analysisNum' => 0,
            'resourcesNum' => 0,
            'resourcesIncreaseNum' => 0,
            'resourcesQuoteNum' => 0,
        );

        if (!empty($statistics)) {
            foreach ($results as $key => &$result) {
                if (!isset($statistics[$key])) {
                    continue;
                }
                $total = $this->getStatisticsDao()->count(array(
                    'termCode' => $termCode,
                    'gt'.$key => 0,
                ));

                $gtCount = $this->getStatisticsDao()->count(array(
                    'gt'.$key => $statistics[$key],
                    'termCode' => $termCode
                ));

                $count = $this->getStatisticsDao()->count(array(
                    $key => $statistics[$key],
                    'termCode' => $termCode
                ));

                $ltCount = $total - $gtCount;
                $gtCount = $gtCount - $count;

                $result = array();
                if ($gtCount > $ltCount) {
                    $result['gtRate'] = $this->countRate($gtCount, $total);
                } else {
                    $result['ltRate'] = $this->countRate($ltCount, $total);
                }
            }
        }

        return $results;
    }

    protected function countRate($count, $total)
    {
        if ($total - 1 <= 0) {
            return ;
        }

        $rate = round($count / ($total - 1), 3);

        if ($rate > 1) {
            $rate = 1;
        } elseif ($rate < 0) {
            $rate = 0;
        }

        return $rate * 100;
    }

    public function findAttendByTermCode($termCode, $studentAttendRate)
    {
        return $this->searchStatistics(array(
            'studentAttendRate' => $studentAttendRate,
            'termCode' => $termCode
        ), array(), 0, PHP_INT_MAX);
    }

    public function searchStatistics($conditions, $orderBy, $start, $limit)
    {
        return $this->getStatisticsDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function getAvgAttendRateByAllCourse()
    {
        return $this->getStatisticsDao()->getAvgAttendRateByAllCourse();
    }

    public function getAvgLessonRateByAllCourse()
    {
        return $this->getStatisticsDao()->getAvgLessonRateByAllCourse();
    }

    protected function getStatisticsDao()
    {
        return $this->createDao('CustomBundle:Statistics:TeacherCourseStatisticsDao');
    }
}
