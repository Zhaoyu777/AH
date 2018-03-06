<?php

namespace CustomBundle\Controller\CourseStatistics;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class CourseStatisticsController extends BaseController
{
    public function courseStatisticsAction($courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->render('course-manage/dashboard/course-learn-statistics.html.twig', array(
            'course' => $course,
            'courseSet' => $courseSet,
        ));
    }

    public function courseStatisticsDataAction($courseId)
    {

        $statistics = $this->getCourseStatisticsService()->findStatisticsByCourseId($courseId);
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

        $xlessons = array();
        foreach ($lessons as $key => $lesson) {
            $xlessons[$lesson['id']] = "课次".$lesson['number'];
        }

        $avgs = $this->avgStatistics($statistics);

        $result = array();
        foreach ($avgs as $columns => $avg) {
            $result[] = array(
                'value' => $avg,
                'type' => $this->getType($columns),
                'name' => $this->getColums($columns),
                'xData' => array_values($xlessons),
                'data' => $this->statisticsData($columns, $xlessons, $statistics),
            );
        }

        return $this->createJsonResponse($result);
    }

    protected function statisticsData($columns, $xlessons, $statistics)
    {
        $statistics = ArrayToolkit::index($statistics, 'lessonId');
        $result = array();
        foreach ($xlessons as $key => $xlesson) {
            $result[] = isset($statistics[$key][$columns]) ? $statistics[$key][$columns] : 0;
        }

        return $result;
    }

    protected function statisticsArrayMerge($array, $xlessons)
    {
        if (count($array) < count($xlessons)) {
            $count = count($xlessons) - count($array);

            $arrayFill = array_fill(count($array), $count, 0);

            $array = array_merge($array, $arrayFill);
        }

        return $array;
    }

    protected function avgStatistics($statistics)
    {
        $avgs = array(
            'studentAttendRate' => 0,
            'taskInCompletionRate' => 0,
            'taskAfterCompletionRate' => 0,
            'taskBeforeCompletionRate' => 0,
            'evaluationScore' => 0,
        );

        foreach ($avgs as $colum => &$avg) {
            $count = 0;
            $rate = 0;
            foreach ($statistics as $statistic) {
                if (!empty($statistic[$colum])) {
                    $count++;
                    $rate += $statistic[$colum]; 
                }
            }

            $avg = $count <= 0 ? 0 : $rate / $count;
        }
        $totalScore = ArrayToolkit::column($statistics, 'totalScore');

        $avgs['totalScore'] = array_sum($totalScore);

        return $avgs;
    }

    protected function getCourseStatisticsService()
    {
        return $this->getBiz()->service('CustomBundle:Statistics:CourseStatisticsService');
    }

    public function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    public function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    private function getColums($colums)
    {
        $type = array(
            'studentAttendRate' => '平均出勤率',
            'taskInCompletionRate' => '课堂互动参与率',
            'taskAfterCompletionRate' => '课后活动完成率',
            'taskBeforeCompletionRate' => '课前活动完成率',
            'evaluationScore' => '课堂平均满意度',
            'totalScore' => '发放总积分',
        );

        return isset($type[$colums]) ? $type[$colums] : '0.0';
    }

    private function getType($colums)
    {
        $type = array(
            'studentAttendRate' => 'percent',
            'taskInCompletionRate' => 'percent',
            'taskAfterCompletionRate' => 'percent',
            'taskBeforeCompletionRate' => 'percent',
            'evaluationScore' => 'decimals',
            'totalScore' => 'number',
        );

        return isset($type[$colums]) ? $type[$colums] : '0.0';
    }
}
