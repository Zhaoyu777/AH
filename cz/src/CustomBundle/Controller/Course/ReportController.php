<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Common\Paginator;
use AppBundle\Common\PHPExcelToolkit;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\DeviceToolkit;

class ReportController extends BaseController
{
    public function teacherReportAction($courseId, $lessonId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            $url = $this->generateUrl('weixin_login');
            $goto = $this->generateUrl('instant_course_teacher_class_report', array('courseId' => $courseId, 'lessonId' => $lessonId), true);
            return $this->redirect($url."?goto=".$goto);
        }
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        if ($lesson['status'] != 'teached') {
            return $this->createMessageResponse('info', '课次未完成，未生成课堂报告！');
        }

        return $this->render('course-manage/class-report/teacher-report/index.html.twig', array(
            'course' => $course,
            'lesson' => $lesson,
            'courseSet' => $courseSet,
        ));
    }

    public function teacherReportBaseAction($courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);

        return $this->render('course-manage/class-report/teacher-report/base-info.html.twig', $this->getReportBaseInfo($course, $lessonId));
    }

    public function exportTestpaperResultAction($courseId, $lessonId, $taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $user = $this->getCurrentUser();
        $fileName = "{$task['title']}成绩.xls";
        $execelInfo = $this->_makeInfo($user);
        $testpapers = $this->getRandomTestpaperService()->buildExportData($courseId, $taskId);

        $objWriter = PHPExcelToolkit::export($testpapers, $execelInfo);
        $this->_setHeader($fileName);
        $objWriter->save('php://output');
    }

    private function _makeInfo($user)
    {
        $title = array(
            'truename' => '姓名',
            'number' => '学号',
            'times' => '测验次数',
            'score' => '首次测验成绩',
            'testTime' => '首次测验时间',
            'maxScore' => '最好成绩',
        );
        $info = array();
        $info['title'] = $title;
        $info['creator'] = $user['truename'];
        $info['sheetName'] = '测验成绩';

        return $info;
    }

    protected function _setHeader($filename)
    {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename={$filename}");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
    }

    public function studentReportBaseAction($courseId, $lessonId)
    {
        $course = $this->getCourseService()->getCourse($courseId);

        return $this->render('course-manage/class-report/student-report/base-info.html.twig', $this->getReportBaseInfo($course, $lessonId));
    }

    private function getReportBaseInfo($course, $lessonId)
    {
        $user = $this->getCurrentUser();
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        $term = $this->getCourseService()->getTermByShortCode($course['termCode']);

        $teachers = $this->getCourseMemberService()->findCourseTeachers($course['id']);
        $teachers = ArrayToolkit::index($teachers, 'userId');
        $userIds = ArrayToolkit::column($teachers, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $masterTeacher = reset($teachers);

        if (!empty($masterTeacher)) {
            $masterTeacherId = $masterTeacher['userId'];
            $masterTeacher = $users[$masterTeacherId]['truename'];
            unset($teachers[$masterTeacherId]);
        } else {
            $masterTeacher = '';
        }

        $assistantTeachers = '';
        foreach ($teachers as $key => $teacher) {
            $assistantTeachers .= ','.$users[$teacher['userId']]['truename'];
        }
        $assistantTeachers = trim($assistantTeachers, ',');

        return array(
            'term' => $term,
            'user' => $user,
            'course' => $course,
            'lesson' => $lesson,
            'courseSet' => $courseSet,
            'masterTeacher' => $masterTeacher,
            'assistantTeachers' => $assistantTeachers,
        );
    }

    public function teacherReportSignInAction($courseId, $lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $first = $this->getSignInService()->getSignInByLessonIdAndTime($lessonId, 1);
        $userIds = array();
        if (!empty($first)) {
            $first['attendList'] = $this->getSignInService()->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 1, 'attend', 5);
            $userIds = ArrayToolkit::column($first['attendList'], 'userId');
        }

        $second = $this->getSignInService()->getSignInByLessonIdAndTime($lessonId, 2);
        if (!empty($second)) {
            $second['attendList'] = $this->getSignInService()->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 2, 'attend', 5);
            $secondUserIds = ArrayToolkit::column($second['attendList'], 'userId');
            $userIds = array_merge($userIds, $secondUserIds);
        }

        $users = $this->getUserService()->findUsersByIds($userIds);
        $signInAnalysis = $this->getSignInService()->analysisSignInByLessonId($lessonId);

        return $this->render('course-manage/class-report/teacher-report/sign-in-info.html.twig', array(
            'signIns' => array($first, $second),
            'users' => $users,
            'lesson' => $lesson,
            'courseId' => $courseId,
            'signInAnalysis' => $signInAnalysis,
        ));
    }

    public function teacherReportSignInDetailAction($courseId, $lessonId, $time)
    {
        $signIn = $this->getSignInService()->getSignInByLessonIdAndTime($lessonId, $time);

        $members = $this->getSignInService()->findSignInMembersBySignInId($signIn['id']);

        $userIds = ArrayToolkit::column($members, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $members = ArrayToolkit::group($members, 'status');
        array_walk(
            $members,
            function (&$member) {
                $member = ArrayToolkit::index($member, 'userId');
            }
        );

        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        return $this->render('course-manage/class-report/teacher-report/detail/sign-in-detail.html.twig', array(
            'members' => $members,
            'users' => $users,
            'signIn' => $signIn,
            'lessonId' => $lessonId,
            'lesson' => $lesson,
            'profiles' => $profiles,
        ));
    }

    public function teacherReportScoreDetailAction(Request $request, $courseId, $lessonId)
    {
        $conditions = array(
            'lessonId' => $lessonId,
            'minScore' => 1,
        );
        $paginator = new Paginator(
            $request,
            $this->getScoreService()->countScores($conditions),
            10
        );

        $scores = $this->getScoreService()->searchScores(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $userIds = ArrayToolkit::column($scores, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('course-manage/class-report/teacher-report/detail/score-detail.html.twig', array(
            'scores' => $scores,
            'users' => $users,
            'paginator' => $paginator,
        ));
    }

    public function studentReportSignInAction($courseId, $lessonId)
    {
        $user = $this->getCurrentUser();
        $signInRecords = $this->getSignInService()->findSignInMembersByLessonIdAndUserId($lessonId, $user['id']);

        return $this->render('course-manage/class-report/student-report/sign-in-info.html.twig', array(
            'signInRecords' => $signInRecords,
            'continuousNumber' => $this->getSignInService()->countSignContinuousByUserId($user['id']),
        ));
    }

    public function teacherReportTaskAction($courseId, $lessonId)
    {
        return $this->render('course-manage/class-report/teacher-report/task-info.html.twig', $this->getTaskInfo($courseId, $lessonId));
    }

    public function teacherReportTaskShowAction($courseId, $lessonId, $taskId)
    {
        return $this->render('course-manage/class-report/teacher-report/detail/task-detail.htm.twig', array(
            'courseId' => $courseId,
            'lessonId' => $lessonId,
            'taskId' => $taskId,
        ));
    }

    public function studentReportTaskAction($courseId, $lessonId)
    {
        $results = $this->getTaskInfo($courseId, $lessonId);
        $taskIds = ArrayToolkit::column($results['tasks'], 'id');
        $results['taskResults'] = $this->getTaskResultService()->findUserTaskResultsByTaskIds($taskIds);
        $results['taskResults'] = ArrayToolkit::index($results['taskResults'], 'courseTaskId');

        return $this->render('course-manage/class-report/student-report/task-info.html.twig', $results);
    }

    private function getTaskInfo($courseId, $lessonId)
    {
        $chapters = $this->getCourseService()->findChaptersByLessonId($lessonId);
        $categoryIds = ArrayToolkit::column($chapters, 'id');

        $tasks = $this->getTaskService()->findTasksByCategoryIds($categoryIds);
        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds);
        $activities = ArrayToolkit::index($activities, 'id');
        foreach ($tasks as $key => &$task) {
            $task['activity'] = $activities[$task['activityId']];
        }

        $tasks = ArrayToolkit::index($tasks, 'categoryId');

        $lessonChapters = $this->getCourseLessonService()->findChaptersByLessonId($lessonId);
        $lessonChapters = ArrayToolkit::index($lessonChapters, 'categoryId');

        foreach ($chapters as $key => &$chapter) {
            if ($chapter['type'] == 'lesson') {
                $chapter['task'] = $tasks[$chapter['id']];
                continue;
            }

            $chapter['chapter'] = $lessonChapters[$chapter['id']];
        }
        $chapters = ArrayToolkit::group($chapters, 'stage');

        return array(
            'chapters' => $chapters,
            'courseId' => $courseId,
            'lessonId' => $lessonId,
            'tasks' => $tasks,
        );
    }

    public function teacherReportScoreAction($courseId, $lessonId)
    {
        $score = $this->getScoreService()->sumScoresByLessonId($lessonId);
        $studentCount = $this->getScoreService()->countUserByLessonId($lessonId);

        return $this->render('course-manage/class-report/teacher-report/score-info.html.twig', array(
            'score' => $score,
            'studentCount' => $studentCount,
            'courseId' => $courseId,
            'lessonId' => $lessonId,
        ));
    }

    public function studentReportScoreAction($courseId, $lessonId)
    {
        $user = $this->getCurrentUser();
        $scores = $this->getScoreService()->findScoresByLessonIdAndUserId($lessonId, $user['id']);
        $score = ArrayToolkit::column($scores, 'score');
        $sum = array_sum($score);
        $taskIds = ArrayToolkit::column($scores, 'taskId');
        $tasks = $this->getTaskService()->findTasksByIds($taskIds);
        $tasks = ArrayToolkit::index($tasks, 'id');

        return $this->render('course-manage/class-report/student-report/score-info.html.twig', array(
            'scores' => $scores,
            'tasks' => $tasks,
            'sum' => $sum,
        ));
    }

    public function studentReportAction($courseId, $lessonId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            $url = $this->generateUrl('weixin_login');
            $goto = $this->generateUrl('instant_course_student_class_report', array('courseId'=>$courseId,'lessonId'=>$lessonId), true);
            return $this->redirect($url."?goto=".$goto);
        }
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        if ($lesson['status'] != 'teached') {
            return $this->createMessageResponse('info', '课次未完成，未生成课堂报告！');
        }

        return $this->render('course-manage/class-report/student-report/index.html.twig', array(
            'course' => $course,
            'lesson' => $lesson,
            'courseSet' => $courseSet,
        ));
    }

    public function studentReportEvaluationAction($courseId, $lessonId)
    {
        $user = $this->getCurrentUser();
        $evaluation = $this->getEvaluationService()->getEvaluationByLessonIdAndUserId($lessonId, $user['id']);

        return $this->render('course-manage/class-report/student-report/evaluation.html.twig', array(
            'evaluation' => $evaluation,
        ));
    }

    public function teacherReportEvaluationAction($courseId, $lessonId)
    {
        $evaluations = $this->getEvaluationService()->findEvaluationsByLessonId($lessonId);
        $score = 0;
        $count = count($evaluations);
        foreach ($evaluations as $key => $evaluation) {
            $score += $evaluation['score'];
        }

        if (!empty($evaluations)) {
            $score /= $count;
        }

        return $this->render('course-manage/class-report/teacher-report/evaluation.html.twig', array(
            'score' => $score,
            'count' => $count,
        ));
    }

    public function studentCustomReportAction(Request $request,$lessonId, $userId)
    {
        $page = $request->query->get('page');
        $fields = array(
            'lessonId' => $lessonId,
            'userId' => $userId,
            'page' => $page,
        );

        $reset = $this->getReportService()->getStudentReport($fields);

        return $this->render('course-manage/class-report/custom-student-report/'.$reset['page'].'.html.twig', $reset['data']);
    }

    protected function getRandomTestpaperService()
    {
        return $this->createService('CustomBundle:RandomTestpaper:RandomTestpaperService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getScoreService()
    {
        return $this->createService('CustomBundle:Score:ScoreService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getActivityService()
    {
        return $this->createService('CustomBundle:Activity:ActivityService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('CustomBundle:Task:TaskResultService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getReportService()
    {
        return $this->createService('CustomBundle:Report:StudentLessonReportService');
    }
}
