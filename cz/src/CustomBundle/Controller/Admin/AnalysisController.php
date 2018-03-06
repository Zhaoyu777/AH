<?php

namespace CustomBundle\Controller\Admin;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class AnalysisController extends BaseController
{
    public function indexAction(Request $request)
    {
        $teacherOnlineCount = $this->getStatisticsService()->countTeacherOnline(15 * 60);
        $studentOnlineCount = $this->getStatisticsService()->countStudentOnline(15 * 60);

        $records = $this->getLogService()->searchLogs(
            array('action' => 'start_course_lesson'),
            'created',
            0,
            10
        );
        foreach ($records as &$record) {
            $courseId = $record['data']['courseId'];
            $course = $this->getCourseService()->getCourse($courseId);
            $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
            $record['courseSetTitle'] = $courseSet['title'];
        }

        $userIds = ArrayToolkit::column($records, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $orgCode = $request->query->get('orgCode', '1.');
        $currentTermPrepareCourseCount = $this->getPrepareCourseLogService()->countCurrentTermLogByOrgCode($orgCode);
        $classReportCounts = $this->classReportStatistic($request);

        return $this->render('admin/czie-analysis/index.html.twig', array(
            'records' => $records,
            'users' => $users,
            'studentOnlineCount' => $studentOnlineCount,
            'teacherOnlineCount' => $teacherOnlineCount,
            'dataInfo' => $this->getDateInfo(),
            'classReportCounts' => $classReportCounts,
            'currentTermPrepareCourseCount' => $currentTermPrepareCourseCount,
        ));
    }

    public function classReportStatistic($request)
    {
        $orgCode = $request->query->get('orgCode', '1.');
        $dateInfo = $this->getDateInfo();

        $dayCount = $this->getCourseLessonService()->countClassReportsByOrgCodeAndTimeRange($orgCode, strtotime($dateInfo['today']), strtotime($dateInfo['today'].'+1 day'));
        $weekCount = $this->getCourseLessonService()->countClassReportsByOrgCodeAndTimeRange($orgCode, strtotime($dateInfo['currentWeekStart']), strtotime($dateInfo['currentWeekEnd'].'+1 day'));
        $monthCount = $this->getCourseLessonService()->countClassReportsByOrgCodeAndTimeRange($orgCode, strtotime($dateInfo['currentMonthStart']), strtotime($dateInfo['currentMonthEnd'].'+1 day'));

        return array(
            'day' => $dayCount,
            'week' => $weekCount,
            'month' => $monthCount,
        );
    }

    public function fileStatisticAction(Request $request)
    {
        $orgCode = $request->query->get('orgCode', '1.');
        $startTime = $request->query->get('startTime', date('Y-m-d', time()));
        $endTime = $request->query->get('endTime', date('Y-m-d', time()));

        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime.'+1 day');

        $fileStatistics = $this->getUploadFileService()->countFileByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);
        $fileStatistics = $this->buildFileStatistics($fileStatistics);

        return $this->createJsonResponse($fileStatistics);
    }

    public function shareCountAction(Request $request)
    {
        $orgCode = $request->query->get('orgCode', '1.');
        $startTime = $request->query->get('startShareTime', date('Y-m-d', time()));
        $endTime = $request->query->get('endShareTime', date('Y-m-d', time()));

        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime.'+1 day');

        $count = $this->getCourseShareService()->countShareByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);

        return $this->createJsonResponse($count);
    }

    private function getDateInfo()
    {
        $today = date('Y-m-d', time());
        $currentWeekStart = date('Y-m-d', strtotime(date('Y-m-d').'-1 Week Monday'));

        if (date('Y-m-d', strtotime($today.'-7 day')) == $currentWeekStart) {
            $currentWeekStart = $today;
        }
        $currentWeekEnd = date('Y-m-d', strtotime($currentWeekStart.'+6 day'));

        $currentMonthStart = date('Y-m-01', time());
        $currentMonthEnd = date('Y-m-d', strtotime($currentMonthStart.'+1 month -1 day'));

        return array(
            'today' => $today,
            'currentWeekStart' => $currentWeekStart,
            'currentWeekEnd' => $currentWeekEnd,
            'currentMonthStart' => $currentMonthStart,
            'currentMonthEnd' => $currentMonthEnd,
        );
    }

    private function buildFileStatistics($fileStatistics)
    {
        $statistic = array(
            'video' => array('name' => '视频', 'count' => 0),
            'audio' => array('name' => '音频', 'count' => 0),
            'document' => array('name' => '文档', 'count' => 0),
            'ppt' => array('name' => 'ppt', 'count' => 0),
        );

        array_walk(
            $fileStatistics,
            function ($fileStatistic) use (&$statistic) {
                if (array_key_exists($fileStatistic['type'], $statistic)) {
                    $statistic[$fileStatistic['type']]['count'] = $fileStatistic['count'];
                }
            }
        );

        return array(
            'sourceTypes' => ArrayToolkit::column($statistic, 'name'),
            'sourceCounts' => ArrayToolkit::column($statistic, 'count'),
        );
    }

    public function overViewStatisticAction(Request $request)
    {
        $fileCount = $this->getUploadFileService()->countSuccessfulFiles();
        $attendRate = $this->getTeacherCourseStatisticsService()->getAvgAttendRateByAllCourse();
        $prepareCourseRate = $this->getTeacherCourseStatisticsService()->getAvgLessonRateByAllCourse();
        $platformRate = '23%';
        $universityTeachingAimFinishedRate = $this->getTeachingAimActivityService()->calcCollegeFinishedRate('1.');

        return $this->render('admin/czie-analysis/overview.html.twig', array(
            'attendRate' => $attendRate,
            'prepareCourseRate' => $prepareCourseRate,
            'platformRate' => $platformRate,
            'fileCount' => $fileCount,
            'universityTeachingAimFinishedRate' => $universityTeachingAimFinishedRate,
        ));
    }

    public function teachersStatisticAction(Request $request)
    {
        return $this->render('admin/czie-analysis/teachers.html.twig', array(
            'isTeachingTeacher' => 'on',
        ));
    }

    public function loadTeachersStatisticAction(Request $request)
    {
        $isTeachingTeacher = $request->query->get('isTeachingTeacher');
        $conditions = $request->query->all();
        if ($isTeachingTeacher == 'on') {
            $term = $this->getCourseService()->getCurrentTerm();
            $conditions = array(
                'orgCode' => $request->query->get('orgCode', '1.'),
                'queryField' => $request->query->get('queryField'),
                'termCode' => $term['shortCode'],
            );
            $userCount = $this->getUserService()->countAnalysisTeachers($conditions);

            $paginator = new Paginator(
                $this->get('request'),
                $userCount,
                20
            );

            $teachers = $this->getUserService()->searchAnalysisTeachers(
                $conditions,
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );
            $userIds = ArrayToolkit::column($teachers, 'userId');
            $users = $this->getUserService()->findUsersByIds($userIds);
        } else {
            $condition = array(
                'orgCode' => $request->query->get('orgCode', '1.'),
                'queryField' => $request->query->get('queryField'),
            );

            $condition['roles'] = 'ROLE_TEACHER';

            $userCount = $this->getUserService()->countAllUsers($condition);

            $paginator = new Paginator(
                $this->get('request'),
                $userCount,
                20
            );

            $users = $this->getUserService()->searchAllUsers(
                $condition,
                array('loginTime' => 'DESC'),
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );

            $userIds = ArrayToolkit::column($users, 'id');
        }
        foreach ($users as $key => &$user) {
            $orgCodes = explode('.', $user['orgCode']);
            if (!empty($orgCodes[2])) {
                $user['orgId'] = $orgCodes[2];
            }
        }

        $term = $this->getCourseService()->getCurrentTerm();

        $courseCounts = $this->getCourseService()->countInstantCourseByUserIdsAndTermCodeAndRoleGroupUserId($userIds, $term['shortCode'], 'teacher');

        return $this->render('admin/czie-analysis/teachers-tr.html.twig', array(
            'users' => $users,
            'userCount' => $userCount,
            'courseCounts' => $courseCounts,
            'paginator' => $paginator,
        ));
    }

    public function teacherCoursesAction(Request $request)
    {
        $userId = $request->query->get('userId');
        $term = $this->getCourseService()->getCurrentTerm();

        $courses = $this->getCourseService()->findInstantCoursesByUserIdAndTermCodeAndRole($userId, $term['shortCode'], 'teacher');
        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        return $this->render('admin/czie-analysis/teacher-courses.html.twig', array(
            'courses' => $courses,
            'courseSets' => $courseSets,
        ));
    }

    public function teacherDetailAction(Request $request)
    {
        $userId = $request->query->get('userId');
        $user = $this->getUserService()->getUser($userId);

        $termCode = $this->getCourseService()->getCurrentTerm();
        $statistics = $this->getTeacherCourseStatisticsService()->getStatisticsByUserIdAndTermCode($user['id'], $termCode['shortCode']);

        $percentage = $this->getTeacherCourseStatisticsService()->getStatisticsPercentageByUserIdAndTermCode($user['id'], $termCode['shortCode']);

        return $this->render('admin/czie-analysis/teacher-detial.html.twig', array(
            'statistics' => $statistics,
            'percentage' => $percentage,
            'user' => $user,
            'termCode' => $termCode,
        ));
    }

    public function studentsStatisticAction(Request $request)
    {
        $orgCode = $request->query->get('orgCode');
        $condition = array(
            'orgCode' => $orgCode,
            'queryField' => $request->query->get('queryField'),
        );

        if (empty($orgCode)) {
            $condition['role'] = '|ROLE_USER|';
            $userCount = $this->getUserService()->countAllUsers($condition);
            $paginator = new Paginator(
                $this->get('request'),
                $userCount,
                20
            );

            $users = $this->getUserService()->searchAllUsers(
                $condition,
                array(),
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );

            $userIds = ArrayToolkit::column($users, 'id');
            $term = $this->getCourseService()->getCurrentTerm();
            $students = $this->getStudentService()->findStudentsByUserIds($userIds);
            $students = ArrayToolkit::index($students, 'userId');
            $courseCounts = $this->getCourseService()->countInstantCourseByUserIdsAndTermCodeAndRoleGroupUserId($userIds, $term['shortCode'], 'student');
        } else {
            $userCount = $this->getStudentService()->countStudents($condition);

            $paginator = new Paginator(
                $this->get('request'),
                $userCount,
                20
            );

            $students = $this->getStudentService()->searchStudents(
                $condition,
                array(),
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );
            $students = ArrayToolkit::index($students, 'userId');

            $userIds = ArrayToolkit::column($students, 'userId');
            $users = $this->getUserService()->findUsersByIds($userIds);

            $term = $this->getCourseService()->getCurrentTerm();

            $courseCounts = $this->getCourseService()->countInstantCourseByUserIdsAndTermCodeAndRoleGroupUserId($userIds, $term['shortCode'], 'student');
        }

        return $this->render('admin/czie-analysis/students.html.twig', array(
            'users' => $users,
            'students' => $students,
            'courseCounts' => $courseCounts,
            'userCount' => $userCount,
            'paginator' => $paginator,
        ));
    }

    public function studentCoursesAction(Request $request)
    {
        $userId = $request->query->get('userId');
        $term = $this->getCourseService()->getCurrentTerm();

        $courses = $this->getCourseService()->findInstantCoursesByUserIdAndTermCodeAndRole($userId, $term['shortCode'], 'student');
        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);
        $courseIds = ArrayToolkit::column($courses, 'id');
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseIds($courseIds);
        $lessons = ArrayToolkit::group($lessons, 'courseId');

        return $this->render('admin/czie-analysis/student-courses.html.twig', array(
            'courses' => $courses,
            'userId' => $userId,
            'lessons' => $lessons,
            'courseSets' => $courseSets,
        ));
    }

    protected function getTeachingAimActivityService()
    {
        return $this->createService('CustomBundle:Lesson:TeachingAimActivityService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:Memberservice');
    }

    protected function getUploadFileService()
    {
        return $this->createService('CustomBundle:File:UploadFileService');
    }

    protected function getStatisticsService()
    {
        return $this->createService('CustomBundle:System:StatisticsService');
    }

    protected function getPrepareCourseLogService()
    {
        return $this->createService('CustomBundle:Course:PrepareCourseLogService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getStudentService()
    {
        return $this->createService('CustomBundle:User:CzieStudentService');
    }

    protected function getTeacherCourseStatisticsService()
    {
        return $this->createService('CustomBundle:Statistics:TeacherCourseStatisticsService');
    }

    protected function getLogService()
    {
        return $this->createService('CustomBundle:System:LogService');
    }

    protected function getCourseShareService()
    {
        return $this->createService('CustomBundle:Course:CourseShareService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getOrgService()
    {
        return $this->createService('CustomBundle:Org:OrgService');
    }
}
