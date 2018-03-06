<?php

namespace CustomBundle\Biz\Statistics\Statistics;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CourseStatistics
{
    private $courseId;

    private $studentCount;

    public function statistics($lesson)
    {
        if ($lesson['memberCount'] <= 0) {
            return;
        }
        $this->courseId = $lesson['courseId'];
        $this->studentCount = $lesson['memberCount'];
        $this->statisticsLesson($lesson['id']);
    }

    public function statisticsLesson($lessonId)
    {
        $statistics = $this->getCourseStatisticsService()->getStatisticsByLessonId($lessonId);

        $fields = array(
            'courseId' => $this->courseId,
            'lessonId' => $lessonId
        );
        if ($this->studentCount) {
            foreach ($this->getUpdateColumns() as $column) {
                $func = 'get'.ucfirst($column);
                $rate = $this->$func($lessonId);
                $fields[$column] = $rate === null ? null : round($rate, 3);
            }
        }

        if (empty($statistics)) {
            $statistics = $this->getCourseStatisticsService()->createStatistics($fields);
        } else {
            $statistics = $this->getCourseStatisticsService()->updateStatistics($statistics['id'], $fields);
        }

        return $statistics;
    }

    //学生出勤率
    public function getStudentAttendRate($lessonId)
    {
        $signIn = $this->getSignInService()->findSignInsByLessonId($lessonId);
        $signInCount = count($signIn);

        if ($signInCount < 1) {
            return 0;
        } elseif ($signInCount == 1) {
            $count = $this->getSignInService()->countSignInMembers(array(
                'lessonId' => $lessonId,
                'status' => 'attend',
            ));
        } elseif ($signInCount == 2) {
            $signInOne = $this->getSignInService()->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 1, 'attend');
            $userIdsOne = ArrayToolkit::column($signInOne, 'userId');
            $signInTwo = $this->getSignInService()->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 2, 'attend');
            $userIdsTwo = ArrayToolkit::column($signInTwo, 'userId');

            $count = count(array_intersect($userIdsOne, $userIdsTwo));
        }

        return $this->checkRate($count / $this->studentCount);
    }

    //课中任务完成率
    protected function getTaskInCompletionRate($lessonId)
    {
        $tasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, "in");
        $taskIds = ArrayToolkit::column($tasks, 'taskId');
        $tasks = $this->getTaskService()->findInteractiveTaskByIds($taskIds);
        $taskIds = ArrayToolkit::column($tasks, 'id');
        if (empty($taskIds)) {
            return null;
        }
        $counts = $this->getTaskService()->countStudentResultByTaskIds($taskIds);
        $rate = array();
        foreach ($counts as $key => $count) {
            $rate[] = $this->checkRate($count['count'] / $this->studentCount);
        }

        return empty($counts) ? 0 : array_sum($rate)/count($rate);
    }

    //课后任务完成率
    protected function getTaskAfterCompletionRate($lessonId)
    {
        $tasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, "after");
        if (empty($tasks)) {
            return null;
        }

        $taskIds = ArrayToolkit::column($tasks, 'taskId');
        $counts = $this->getTaskService()->countStudentResultByTaskIds($taskIds);
        $rate = array();
        foreach ($counts as $key => $count) {
            $rate[] = $this->checkRate($count['count'] / $this->studentCount);
        }

        return empty($counts) ? 0 : array_sum($rate)/count($rate);
    }

    protected function getTaskBeforeCompletionRate($lessonId)
    {
        $tasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, "before");
        if (empty($tasks)) {
            return null;
        }

        $taskIds = ArrayToolkit::column($tasks, 'taskId');
        $counts = $this->getTaskService()->countStudentResultByTaskIds($taskIds);
        $rate = array();
        foreach ($counts as $key => $count) {
            $rate[] = $count['count'] / $this->studentCount;
        }

        return empty($counts) ? 0 : array_sum($rate)/count($rate);
    }

    protected function getEvaluationScore($lessonId)
    {
        return $this->getLessonEvaluationService()->getScoreAvgByLessonId($lessonId);
    }

    protected function getTotalScore($lessonId)
    {
        return $this->getScoreService()->sumScoresByLessonId($lessonId);
    }

    protected function getTeachingAimsFinishedRate($lessonId)
    {
        return $this->getTeachingAimActivityService()->calcLessonFinishedRate($lessonId);
    }

    protected function getCourseStatisticsService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Statistics:CourseStatisticsService');
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getSignInService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getCourseMemberService()
    {
        return $this->getServiceKernel()->createService('Course:MemberService');
    }

    protected function getEvaluationService()
    {
        return $this->getServiceKernel()->createService('Lesson:Evaluation');
    }

    protected function getLogService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:System:LogService');
    }

    protected function getScoreService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Score:ScoreService');
    }

    protected function getTaskService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->getServiceKernel()->createService('Activity:ActivityService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

    protected function getBrainStormResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Activity:BrainStormResultService');
    }

    protected function getDisplayWallResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:DisplayWall:ResultService');
    }

    protected function getOneSentenceResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Activity:OneSentenceResultService');
    }

    protected function getQuestionnaireService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getTestpaperService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Testpaper:TestpaperService');
    }

    public function getLessonEvaluationService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Lesson:EvaluationService');
    }

    protected function getTeachingAimActivityService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Lesson:TeachingAimActivityService');
    }

    protected function checkRate($rate)
    {
        if ($rate > 1) {
            return 1;
        } elseif ($rate < 0) {
            return 0;
        }

        return $rate;
    }

    private function getUpdateColumns()
    {
        return array(
            'studentAttendRate',
            'taskInCompletionRate',
            'taskAfterCompletionRate',
            'taskBeforeCompletionRate',
            'evaluationScore',
            'totalScore',
            'teachingAimsFinishedRate',
        );
    }
}
