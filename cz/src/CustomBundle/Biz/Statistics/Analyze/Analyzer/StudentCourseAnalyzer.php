<?php

namespace CustomBundle\Biz\Statistics\Analyze\Analyzer;

use AppBundle\Common\ArrayToolkit;

class StudentCourseAnalyzer extends Analyzer
{
    protected $courseIds = array();

    protected $studentInfos = array(
        'courseId' => 0,
        'userId' => 0,
        'lessonIds' => array()
    );

    // 某学生参加过的课次Id
    protected $lessonIds;

    // 参加过的课次，发起的签到情况
    protected $expertSignInLessonsTimes;

    // 参加过的课次，拥有的所有任务
    protected $allLessonsTasks;

    // 参加过的课次，拥有的所有试卷
    protected $allTestpapers;

    // 学生完成的任务的Id
    protected $studentFinishTasksIds;

    public function initParameters($options)
    {
        $this->courseIds = $options['courseIds'];
    }

    public function analyze()
    {
        $courseIds = $this->getParameter('courseIds');

        if (empty($courseIds)) {
            return;
        }

        $allSignInMembers = $this->getSignInService()->findSignInMembersByCourseIds($courseIds);
        $allSignInMembers = ArrayToolkit::group($allSignInMembers, 'courseId');

        $results = array();
        foreach ($courseIds as $courseId) {
            $allStudentsLessons = $this->getStudentLessonReportService()->findReportBycourseId($courseId);
            $outCourseSignInMembers = empty($allSignInMembers[$courseId]) ? array() : $allSignInMembers[$courseId];
            $allStudentsLessons = array_merge($allStudentsLessons, $outCourseSignInMembers);
            $allStudentsLessons = ArrayToolkit::group($allStudentsLessons, 'userId');

            foreach ($allStudentsLessons as $userId => $studentLessons) {
                list(
                    $allCourseScores,
                    $courseAverageGrades,
                    $courseAttendence,
                    $courseTaskInCompletionRates,
                    $courseTaskOutCompletionRates
                ) = $this->calculateEveryDatas($userId, $studentLessons, $courseId);

                $results[] = array(
                    'studentAttendence' => $this->formatData($courseAttendence),
                    'totalScore' => $allCourseScores,
                    'averageGrades' => $this->formatData($courseAverageGrades),
                    'taskInCompletionRate' => $this->formatData($courseTaskInCompletionRates),
                    'taskOutCompletionRate' => $this->formatData($courseTaskOutCompletionRates),
                    'courseId' => $courseId,
                    'userId' => $userId,
                );
            }
        }

        $this->analyzeResults = $results;
    }

    public function save()
    {
        if (empty($this->analyzeResults)) {
            return;
        }

        $this->beginTransaction();
        try {
            foreach ($this->analyzeResults as $result) {
                if ($exsitStatistics = $this->getStudentCourseStatisticsService()->getStudentsCourseStatisticsByUserIdAndCourseId($result['userId'], $result['courseId'])) {
                    $this->getStudentCourseStatisticsService()->updateStudentsCourseStatistics($exsitStatistics['id'], $result);
                } else {
                    $this->getStudentCourseStatisticsService()->createStudentsCourseStatistics($result);
                }
            }

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function calculateEveryDatas($userId, $studentLessons, $courseId)
    {
        $this->studentInfos['lessonIds'] = array();
        $this->studentInfos['userId'] = $userId;
        $this->studentInfos['courseId'] = $courseId;
        $this->studentFinishTasksIds = array();

        $this->prepareBasicDatas($studentLessons);

        // 出勤率
        $studentAttendence = $this->calStudentAttendence();

        // 课内活动完成率、课外活动完成率
        list($taskInCompletionRate, $taskOutCompletionRate) = $this->calTasksCompletionRate();

        // 平时积分
        $studentScores = $this->getScoreService()->countStudentsScoresByUserIdAndLessonIds($userId, $this->studentInfos['lessonIds']);

        // 平时得分
        $averageGrades = $this->calAverageGrades($courseId, $userId);

        return array(
            $studentScores,
            $averageGrades,
            $studentAttendence,
            $taskInCompletionRate,
            $taskOutCompletionRate,
        );
    }

    protected function prepareBasicDatas($studentLessons)
    {
        $this->studentInfos['lessonIds'] = array_unique(ArrayToolkit::column($studentLessons, 'lessonId'));

        $allSignIns = $this->getSignInService()->findEndSignInsByCourseId($this->studentInfos['courseId']);
        $allSignIns = ArrayToolkit::group($allSignIns, 'lessonId');

        $allCourseLessonsTasks = $this->getCourseLessonService()->findLessonTasksByCourseId($this->studentInfos['courseId']);
        $allCourseLessonsTasks = ArrayToolkit::group($allCourseLessonsTasks, 'lessonId');

        $testpapers = $this->getTestpaperService()->findTestpapersByCourseId($this->studentInfos['courseId']);
        $testpapers = ArrayToolkit::group($testpapers, 'lessonId');

        $this->expertSignInLessonsTimes = array();
        $this->allLessonsTasks = array();
        $this->allTestpapers = array();
        foreach ($this->studentInfos['lessonIds'] as $lessonId) {
            if (isset($allSignIns[$lessonId])) {
                $this->expertSignInLessonsTimes[$lessonId] = count($allSignIns[$lessonId]);
            }

            if (isset($allCourseLessonsTasks[$lessonId])) {
                foreach ($allCourseLessonsTasks[$lessonId] as $task) {
                    $this->allLessonsTasks[] = array(
                        'taskId' => $task['taskId'],
                        'stage' => $task['stage'],
                        'lessonId' => $task['lessonId']
                    );
                }
            }

            if (isset($testpapers[$lessonId])) {
                foreach ($testpapers[$lessonId] as $testpaper) {
                    $this->allTestpapers[$testpaper['id']] = array(
                        'score' => $testpaper['score']
                    );
                }
            }
        }
        $commonTeaspapers = $this->getTestpaperService()->findTestpapersByCourseId(0);

        $this->allTestpapers = array_merge($this->allTestpapers, $commonTeaspapers);
        $this->allTestpapers = ArrayToolkit::index($commonTeaspapers, 'id');
        $this->allTestpapers = $this->allTestpapers;
    }

    protected function calStudentAttendence()
    {
        $studentAttendence = null;
        $actualSignInLessons = $this->getSignInService()->findSignInsMemberByUserIdAndCourseIdAndStatus($this->studentInfos['userId'], $this->studentInfos['courseId'], 'attend');
        $actualSignInLessonsIds = ArrayToolkit::column($actualSignInLessons, 'lessonId');
        $actualSignInLessonsTimes = array_count_values($actualSignInLessonsIds);

        if (!empty($this->expertSignInLessonsTimes)) {
            $expertSignIn = 0;
            $actualSignIn = 0;
            foreach ($this->expertSignInLessonsTimes as $lessonId => $times) {
                if (isset($actualSignInLessonsTimes[$lessonId]) && $actualSignInLessonsTimes[$lessonId] == $times) {
                    ++$actualSignIn;
                }
                ++$expertSignIn;
            }

            if ($expertSignIn == 0) {
                $studentAttendence = 0;
            } else {
                $studentAttendence = $actualSignIn / $expertSignIn;
            }
        }

        return $studentAttendence;
    }

    protected function calTasksCompletionRate()
    {
        $lessonsTaskIds = array();
        $courseInTasksIds = array();
        $courseOutLessonTasksIds = array();
        foreach ($this->allLessonsTasks as $lessonTask) {
            if (in_array($lessonTask['lessonId'], $this->studentInfos['lessonIds'])) {
                $lessonsTaskIds[] = $lessonTask['taskId'];

                if ($lessonTask['stage'] != 'in') {
                    $courseOutLessonTasksIds[] = $lessonTask['taskId'];
                } else {
                    $courseInTasksIds[] = $lessonTask['taskId'];
                }
            }
        }

        return array(
            $this->calTasksInCompletionRate($courseInTasksIds, $lessonsTaskIds),
            $this->calTasksOutCompletionRate($courseOutLessonTasksIds),
        );
    }

    protected function fetchStudentFinishTaskIds()
    {
        if (empty($this->studentFinishTasksIds)) {
            $studentFinishTasks = $this->getTaskResultService()->findByCourseIdAndUserId($this->studentInfos['courseId'], $this->studentInfos['userId']);
            $this->studentFinishTasksIds = ArrayToolkit::column($studentFinishTasks, 'courseTaskId');
        }

        return $this->studentFinishTasksIds;
    }

    protected function calTasksInCompletionRate($courseInTasksIds, $lessonsTaskIds)
    {
        $taskInCompletionRate = null;

        $acceptTaskType = array(
            'questionnaire',
            'testpaper',
            'oneSentence',
            'displayWall',
            'brainStorm',
            'practice'
        );
        $tasks = $this->getTaskService()->findTasksByIds($courseInTasksIds);
        $acceptCourseInTasksIds = array();
        foreach ($tasks as $task) {
            if (in_array($task['type'], $acceptTaskType)) {
                $acceptCourseInTasksIds[] = $task['id'];
            }
        }

        if (array_intersect($lessonsTaskIds, $acceptCourseInTasksIds)) {
            $studentFinishTasksIds = $this->fetchStudentFinishTaskIds();
            $taskInCompletionRate = count(array_intersect($studentFinishTasksIds, $acceptCourseInTasksIds)) / count($acceptCourseInTasksIds);
        }

        return $taskInCompletionRate;
    }

    protected function calTasksOutCompletionRate($courseOutLessonTasksIds)
    {
        $taskOutCompletionRate = null;

        if (!empty($courseOutLessonTasksIds)) {
            $studentFinishTasksIds = $this->fetchStudentFinishTaskIds();
            $taskOutCompletionRate = count(array_intersect($studentFinishTasksIds, $courseOutLessonTasksIds)) / count($courseOutLessonTasksIds);
        }

        return $taskOutCompletionRate;
    }

    protected function calAverageGrades($courseId, $userId)
    {
        $averageGrades = null;

        $testResults = $this->getTestpaperService()->findResultsByCourseIdAndUserId($courseId, $userId);
        if (!empty($testResults)) {
            $sums = 0;
            foreach ($testResults as $result) {
                if (!isset($this->allTestpapers[$result['testId']]) || $this->allTestpapers[$result['testId']]['score'] == 0) {
                    continue;
                }

                $sums += $result['score'] / $this->allTestpapers[$result['testId']]['score'];
            }

            if (count($testResults) == 0) {
                $averageGrades = 0;
            } else {
                $averageGrades = $sums / count($testResults);
            }
        }

        return $averageGrades;
    }

    protected function formatData($data)
    {
        if (is_null($data)) {
            return $data;
        }

        if ($data < 0) {
            return 0;
        }

        if ($data > 1) {
            return 100;
        }

        return round($data * 100, 1);
    }

    protected function beginTransaction()
    {
        $biz = $this->getBiz();
        $biz['db']->beginTransaction();
    }

    protected function commit()
    {
        $biz = $this->getBiz();
        $biz['db']->commit();
    }

    protected function rollback()
    {
        $biz = $this->getBiz();
        $biz['db']->rollback();
    }

    protected function getTaskResultService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskResultService');
    }

    protected function getStudentLessonReportService()
    {
        return $this->getBiz()->service('CustomBundle:Report:StudentLessonReportService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseService');
    }

    protected function getBrainStormResultService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:BrainStormResultService');
    }

    protected function getDisplayWallResultService()
    {
        return $this->getBiz()->service('CustomBundle:DisplayWall:ResultService');
    }

    protected function getOneSentenceResultService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:OneSentenceResultService');
    }

    protected function getTestpaperService()
    {
        return $this->getBiz()->service('CustomBundle:Testpaper:TestpaperService');
    }

    protected function getQuestionnaireService()
    {
        return $this->getBiz()->service('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getTaskService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }

    protected function getMemberService()
    {
        return $this->getBiz()->service('Course:MemberService');
    }

    protected function getTestpaperResultService()
    {
        return $this->getBiz()->service('CustomBundle:Testpaper:TestpaperService');
    }

    protected function getScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:ScoreService');
    }

    protected function getSignInService()
    {
        return $this->getBiz()->service('CustomBundle:SignIn:SignInService');
    }

    protected function getStudentCourseStatisticsService()
    {
        return $this->getBiz()->service('CustomBundle:Statistics:StudentCourseStatisticsService');
    }

    protected function getCourseLessonService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseLessonService');
    }
}
