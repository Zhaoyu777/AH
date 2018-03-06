<?php

namespace CustomBundle\Biz\Report\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Report\Service\StudentLessonReportService;

class StudentLessonReportServiceImpl extends BaseService implements StudentLessonReportService
{
    public function create($field)
    {
        $field = ArrayToolkit::parts($field, array(
            'courseId',
            'lessonId',
            'userId',
            'taskInCompletionRate',
            'taskBeforCompletionRate',
            'exerciseNumber',
        ));

        return $this->getReportDao()->create($field);
    }

    public function createAll($fields)
    {
        return $this->getReportDao()->batchCreate($fields);
    }

    public function createLessonReport($lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $members = $this->getCourseMemberService()->findCourseStudents($lesson['courseId'], 0, PHP_INT_MAX);
        $tasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, 'in');

        $taskBeforCompletionRates = $this->findStudentCompletionRate($lessonId, 'before');
        $taskInCompletionRates = $this->findStudentCompletionRate($lessonId, 'in');
        $exerciseNumbers = $this->countInteractiveResultsBylessonId($lessonId);

        $time = time();
        $fields = array();
        foreach ($members as $member) {
            $userId = $member['userId'];
            $fields[] = array(
                'courseId' => $lesson['courseId'],
                'lessonId' => $lessonId,
                'userId' => $userId,
                'taskInCompletionRate' => empty($taskInCompletionRates[$userId]) ? 0 : $taskInCompletionRates[$userId],
                'taskBeforCompletionRate' => empty($taskBeforCompletionRates[$userId]) ? 0 : $taskBeforCompletionRates[$userId],
                'exerciseNumber' => empty($exerciseNumbers[$userId]) ? 0 : $exerciseNumbers[$userId],
            );
        }

        return $this->createAll($fields);
    }

    protected function findStudentCompletionRate($lessonId, $stage)
    {
        $tasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, $stage);
        if (empty($tasks)) {
            return array();
        }
        $taskIds = ArrayToolkit::column($tasks, 'taskId');

        $conditions = array(
            'courseTaskIds' => $taskIds,
            'status' => 'finish',
        );
        $completionRates = array();
        $results = $this->getTaskResultDao()->search($conditions, array(), 0, PHP_INT_MAX);
        $results = ArrayToolkit::group($results, 'userId');
        foreach ($results as $userId => $result) {
            $completionRates[$userId] = round(count($result) / count($tasks), 2);
        }

        return $completionRates;
    }

    public function countInteractiveResultsBylessonId($lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $tasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, 'in');
        $taskIds = ArrayToolkit::column($tasks, 'taskId');
        $tasks = $this->getTaskService()->findInteractiveTaskByIds($taskIds);
        $taskIds = ArrayToolkit::column($tasks, 'id');

        $conditions = array(
            'courseTaskIds' => empty($taskIds) ? array(0) : $taskIds,
            'status' => 'finish',
        );

        $results = $this->getTaskResultDao()->search($conditions, array(), 0, PHP_INT_MAX);
        $results = ArrayToolkit::group($results, 'userId');

        $statistics = array();
        foreach ($results as $userId => $result) {
            $statistics[$userId] = count($result);
        }

        return $statistics;
    }

    public function getbeatRateByLessonIdAndUserId($lessonId, $userId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $memberCount = $this->getCourseMemberService()->getCourseStudentCount($lesson['courseId'])-1;

        $report = $this->getReportBylessonIdAndUserId($lessonId, $userId);
        $rates = array(
            'taskInCompletionRate' => $report['taskInCompletionRate'],
            'taskBeforCompletionRate' => $report['taskBeforCompletionRate'],
            'exerciseNumber' => $report['exerciseNumber'],
        );

        foreach ($rates as $field => &$rate) {
            $count = $this->getReportDao()->count(array(
                "min".ucfirst($field) => $rate,
                'lessonId' => $lessonId,
            ));

            $rate = array(
                'value' => $rate,
                'beatRate' => round($count / $memberCount * 100, 2),
            );
        }

        return $rates;
    }

    public function getStudentReport($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('lessonId', 'userId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $page = array('index', 'homePage', 'inPage', 'beforePage', 'afterPage', 'scorePage', 'signInPage', 'evaluationPage');
        if (!in_array($fields['page'], $page)) {
            $fields['page'] = 'index';
        }
        $pageFun = 'get'.ucfirst($fields['page']).'Data';

        return array(
            'page' => $fields['page'],
            'data' => $this->$pageFun($fields['lessonId'], $fields['userId']),
        );
    }

    protected function getIndexData($lessonId, $userId)
    {
        $user = $this->getUserService()->getUser($userId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $description = "《{$course['title']}》-课次{$lesson['number']}";
        $description = empty($lesson['title']) ? $description : $description.":{$lesson['title']}";

        return array(
            'description' => $description,
            'truename' => $user['truename'],
            'course' => $course,
            'lesson' => $lesson,
            'courseSet' => $courseSet,
            'pages' => $this->findShowPages($lessonId, $userId),
        );
    }

    protected function findShowPages($lessonId, $userId)
    {
        $result = array();
        $user = $this->getCurrentUser();
        if ($user['id'] == $userId) {
            $result['evaluationPage'] = 'evaluationPage';
        }

        $signIns = $this->getSignInService()->findSignInsByLessonId($lessonId);
        if (!empty($signIns)) {
            $result['signInPage'] = 'signInPage';
        }

        return $result;
    }

    protected function getEvaluationPageData($lessonId, $userId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return array('evaluation' => array());
        }
        $evaluation = $this->getLessonEvaluationService()->getEvaluationByLessonIdAndUserId($lessonId, $user['id']);

        return array('evaluation' => $evaluation);
    }

    protected function getSignInPageData($lessonId, $userId)
    {
        $continuousNumber = $this->getSignInService()->countSignContinuousByUserId($userId);
        $signIns = $this->getSignInService()->findSignInMembersByLessonIdAndUserId($lessonId, $userId);
        foreach ($signIns as $key => &$signIn) {
            if ($signIn['status'] != 'attend') {
                unset($signIns[$key]);
            }
        }

        return array(
            'signIns' => $signIns,
            'continuousNumber' => $continuousNumber,
        );
    }

    protected function getScorePageData($lessonId, $userId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $scores = $this->getScoreService()->findUserSumScoresByLessonId($lessonId);

        $scores = ArrayToolkit::index($scores, 'userId');
        if (empty($scores[$userId])) {
            return array(
                'score' => 0,
                'beatRate' => 0,
            );
        }

        return array(
            'score' => $scores[$userId]['scores'],
            'beatRate' => $this->getScoreBeatRate($scores, $lesson, $userId),
        );
    }

    protected function getScoreBeatRate($scores, $lesson, $userId)
    {
        $memberCount = $this->getCourseMemberService()->countMembers(array(
            'courseId' => $lesson['courseId'],
            'role' => 'student',
        ));
        $memberCount--;

        if ($memberCount <= 0) {
            return 0;
        }

        $teachers = $this->getCourseMemberService()->findCourseTeachers($lesson['courseId']);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');

        $userScore = $scores[$userId];
        unset($scores[$userId]);

        $count = $memberCount;
        foreach ($scores as $score) {
            if (in_array($score['userId'], $teacherIds)) {
                continue ;
            }
            if ($score['scores'] > $userScore['scores']) {
                 $count--;
            }
        }

        return round($count / $memberCount * 100, 2);
    }

    protected function getAfterPageData($lessonId, $userId)
    {
        $result = $this->getCommonReport($userId, $lessonId, 'after');
        $report = $this->getAfterbeatRate($lessonId, $userId);

        return array_merge($result, $report);
    }

    protected function getAfterbeatRate($lessonId, $userId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $memberCount = $this->getCourseMemberService()->countMembers(array(
            'courseId' => $lesson['courseId'],
            'role' => 'student',
        ))-1;
        if ($memberCount <= 0) {
            return array('beatRate' => 0);
        }
        $studentFinishTaskRates = $this->findStudentCompletionRate($lessonId, 'after');
        if (empty($studentFinishTaskCount[$userId])) {
            return array('beatRate' => 0);
        }

        $userRate = $studentFinishTaskCount[$userId];

        $count = $memberCount;
        foreach ($studentFinishTaskRates as $userId => $rate) {
            $userRate < $rate ? $count-- : 0;
        }

        return array('beatRate' => round($count / $memberCount * 100, 2));
    }

    protected function getInPageData($lessonId, $userId)
    {
        $result = $this->getCommonReport($userId, $lessonId, 'in');

        $report = $this->getReportBylessonIdAndUserId($lessonId, $userId);
        $result['exerciseBeat'] = $this->getbeatRateByLessonIdAndUserIdAndRate($lessonId, $userId, 'exerciseNumber')['beatRate'];
        $result['taskBeat'] = $this->getbeatRateByLessonIdAndUserIdAndRate($lessonId, $userId, 'taskInCompletionRate')['beatRate'];
        $result['exerciseNumber'] = $report['exerciseNumber'];

        return $result;
    }

    protected function getBeforePageData($lessonId, $userId)
    {
        $result = $this->getCommonReport($userId, $lessonId, 'before');
        $report = $this->getbeatRateByLessonIdAndUserIdAndRate($lessonId, $userId, 'taskBeforCompletionRate');

        return array_merge($result, $report);
    }

    protected function getHomePageData($lessonId, $userId)
    {
        $user = $this->getUserService()->getUser($userId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return array(
            'user' => $user,
            'lesson' => $lesson,
            'course' => $course,
            'courseSet' => $courseSet,
        );
    }

    protected function getbeatRateByLessonIdAndUserIdAndRate($lessonId, $userId, $rate)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $memberCount = $this->getCourseMemberService()->getCourseStudentCount($lesson['courseId'])-1;
        if ($memberCount <= 0) {
            return array('beatRate' => 0);
        }

        $report = $this->getReportBylessonIdAndUserId($lessonId, $userId);

        $condition = "min".ucfirst($rate);
        $count = $this->getReportDao()->count(array(
            $condition => $report[$rate],
            'lessonId' => $lessonId,
        ));

        return array('beatRate' => round($count / $memberCount * 100, 2));
    }

    protected function getCommonReport($userId, $lessonId, $stage)
    {
        $chapters = $this->getChapterDao()->findByLessonIdAndStage($lessonId, $stage);
        $chapters = ArrayToolkit::index($chapters, 'id');
        if (empty($chapters)) {
            return array(
                'taskResults' => array(),
                'beatRate' => 0,
                'finishCount' => 0,
                'taskCounnt' => 0,
            );
        }

        $lessonTasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, $stage);
        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');
        $tasks = $this->getTaskService()->findTasksByIds($taskIds);

        $conditions = array(
            'courseTaskIds' => $taskIds,
            'status' => 'finish',
            'userId' => $userId,
        );
        $results = $this->getTaskResultDao()->search($conditions, array(), 0, PHP_INT_MAX);
        $results = ArrayToolkit::index($results, 'courseTaskId');

        $scores = $this->getScoreService()->findScoresByLessonIdAndUserId($lessonId, $userId);
        $scores = ArrayToolkit::index($scores, 'taskId');

        $finishCount = 0;
        $taskResults = array();
        foreach ($tasks as $task) {
            $taskId = $task['id'];
            $seq    = $chapters[$task['categoryId']]['seq'];
            $status = $this->taskStatus($stage, $task, $results);
            $status == 'finish' ? $finishCount++ : true;
            $taskResults[$seq] = array(
                'title' => $task['title'],
                'status' => $status,
                'score' => empty($scores[$taskId]) ? null : $scores[$taskId]['score'],
            );
        }

        return array(
            'taskResults' => $taskResults,
            'finishCount' => $finishCount,
            'taskCounnt' => count($tasks),
        );
    }

    protected function taskStatus($stage, $task, $results)
    {
        $types = array('displayWall', 'questionnaire', 'brainStorm', 'oneSentence', 'testPaper');

        if ($stage == 'in' && !in_array($task['type'], $types)) {
            $status = 'finish';
        } else {
            $status = empty($results[$task['id']]) ? false : 'finish';
        }

        return $status;
    }

    public function findReportBylessonId($lessonId)
    {
        return $this->getReportDao()->findBylessonId($lessonId);
    }

    public function getReportBylessonIdAndUserId($lessonId, $userId)
    {
        return $this->getReportDao()->getBylessonIdAndUserId($lessonId, $userId);
    }

    public function findReportBycourseId($courseId)
    {
        return $this->getReportDao()->findBycourseId($courseId);
    }

    public function findReportBycourseIdAndUserId($courseId, $userId)
    {
        return $this->getReportDao()->findBycourseIdAndUserId($courseId, $userId);
    }

    public function freshReports()
    {
        return $this->getReportDao()->freshReports();
    }

    public function updateReportTable($allReports)
    {
        try {
            $this->beginTransaction();

            $this->freshReports();
            $this->createAll($allReports);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function getCourseChapterDao()
    {
        return $this->createDao('CustomBundle:Course:CourseChapterDao');
    }

    protected function getReportDao()
    {
        return $this->createDao('CustomBundle:Report:StudentLessonReportDao');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTaskResultDao()
    {
        return $this->createDao('CustomBundle:Task:ResultDao');
    }

    protected function getScoreService()
    {
        return $this->createService('CustomBundle:Score:ScoreService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getChapterDao()
    {
        return $this->createDao('CustomBundle:Course:CourseChapterDao');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getLessonEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }
}