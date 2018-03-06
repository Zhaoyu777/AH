<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class CourseManageController extends BaseController
{
    public function createCourseAction(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->request->all();

            $courseSet = $this->getCourseSetService()->createInstantCourseSet($data);

            $course = $this->generateDefaultCourse($courseSet);
            if (!empty($data['courseTitle'])) {
                $course['title'] = $data['courseTitle'];
            }
            if (!empty($data['termCode'])) {
                $course['termCode'] = $data['termCode'];
            }

            $course = $this->getCourseService()->createCourse($course);
            $this->getCourseSetService()->setDefaultCourseId($courseSet['id'], $course['id']);

            $this->getCourseLessonService()->batchCreateCourseLessons($course['id'], $data['lessonCount']);

            return $this->createJsonResponse(true);
        }

        return $this->render('courseset-manage/create-custom-course-modal.html.twig');
    }

    public function manageAction(Request $request, $courseSetId)
    {
        $termCode = $request->query->get('termCode');
        $courseSet    = $this->getCourseSetService()->tryManageCourseSet($courseSetId);
        $courses      = $this->getCourseService()->findCoursesByCourseSetId($courseSetId);
        $courseIds    = ArrayToolkit::column($courses, 'id');
        $memberCounts = $this->getCourseService()->findStudentCountsByCourseIds($courseIds);
        $courseLessonCounts = $this->getCourseLessonService()->findCourseLessonCountByCourseIds($courseIds);

        return $this->render('prepare-course-manage/course-list.html.twig', array(
            'courseSet'    => $courseSet,
            'courses'      => $courses,
            'courseLessonCounts' => $courseLessonCounts,
            'memberCounts' => $memberCounts,
            'termCode' => $termCode,
        ));
    }

    public function deleteAction($courseSetId)
    {
        $this->getCourseSetService()->deleteInstantCourseSet($courseSetId);
        $this->getCourseService()->deleteCoursesByCourseSetId($courseSetId);

        return $this->createJsonResponse(true);
    }

    public function teachersAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        if ($course['status'] == 'delete') {
            return $this->createMessageResponse('info', '该课程已删除', null, 3000, $this->generateUrl('my_teaching_instant_courses'));
        }
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            if (empty($data) || !isset($data['teachers'])) {
                throw new InvalidArgumentException('Empty Data');
            }
            $teachers = json_decode($data['teachers'], true);
            if (!empty($data['lecturerIds'])) {
                foreach ($data['lecturerIds'] as $lecturerId) {
                    $teachers[] = array(
                        'id' => $lecturerId,
                        'isVisible' => 1,
                    );
                }
            }

            if (empty($teachers)) {
                throw new InvalidArgumentException('Empty Data');
            }

            $members = $this->getCourseMemberService()->searchMembers(
                array('courseId' => $courseId),
                array(),
                0,
                PHP_INT_MAX
            );
            $members = ArrayToolkit::index($members, 'userId');
            $teachersIds = ArrayToolkit::column($teachers, 'id');

            foreach ($teachersIds as $teacherId) {
                $teacherMemberId = empty($members[$teacherId]['id']) ? '' : $members[$teacherId]['id'];
                $this->getGroupMemberSerice()->deleteGroupMemberByCourseMemberId($teacherMemberId);
            }
            $this->getCourseMemberService()->setCourseTeachers($courseId, $teachers);
        }
        $teachers = $this->getCourseService()->findTeachersByCourseId($courseId);
        $teacherIds = array();
        $lecturers = $this->getCourseService()->findLecturersByCourseId($courseId);

        if (!empty($teachers)) {
            foreach ($lecturers as &$lecturer) {
                $user = $this->getUserService()->getUserByNickname($lecturer['jsdm']);
                $lecturer['userId'] = $user['id'];
                $lecturer['jsdm'] = $user['nickname'];
                $lecturer['avatar'] = $this->get('web.twig.extension')->getFilePath($user['smallAvatar'], 'avatar.png');
            }

            foreach ($teachers as $teacher) {
                $avatar = $this->get('web.twig.app_extension')->userAvatar($teacher, 'small');
                if (!in_array($teacher['nickname'], ArrayToolkit::column($lecturers, 'jsdm'))) {
                    $teacherIds[] = array(
                        'id' => $teacher['userId'],
                        'isVisible' => $teacher['isVisible'],
                        'nickname' => $teacher['nickname'],
                        'avatar' => $this->get('web.twig.extension')->getFilePath($avatar, 'avatar.png'),
                    );
                }
            }
        }

        return $this->render('prepare-course-manage/teacher-manage/teachers.html.twig', array(
            'course' => $course,
            'teacherIds' => $teacherIds,
            'courseSet' => $courseSet,
            'lecturers' => $lecturers
        ));
    }

    public function teachersMatchAction(Request $request, $courseId)
    {
        $queryField = $request->query->get('q');

        $teachers = $this->getCourseService()->findTeachersByCourseId($courseId);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');

        $users = $this->getUserService()->searchAllUsers(
            array('queryField' => $queryField, 'roles' => 'ROLE_TEACHER', 'excludeIds' => $teacherIds),
            array('createdTime' => 'DESC'),
            0,
            10
        );

        $teachers = array();

        foreach ($users as $user) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $teachers[] = array(
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'avatar' => $this->getWebExtension()->getFilePath($avatar, 'avatar.png'),
                'isVisible' => 1,
            );
        }

        return $this->createJsonResponse($teachers);
    }

    public function showAction($courseId)
    {

        $user = $this->getCurrentUser();
        $course = $this->getCourseService()->tryManageCourse($courseId);
        if ($course['status'] == 'delete') {
            return $this->createMessageResponse('info', '该课程已删除', null, 3000, $this->generateUrl('my_teaching_instant_courses'));
        }
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);
        $groupLessons = ArrayToolkit::group($lessons, 'status');
        $currentLesson = $this->getCourseLessonService()->getCurrenTeachCourseLesson($courseId);

        list($completedLessons, $notCompletedLessons) = $this->sortInstantCourseLessons($lessons, $currentLesson);
        $lessonCount = count($lessons);
        $completedLessonsCount = count($completedLessons);

        if (!empty($groupLessons['teached'])) {
            $progress = round(count($groupLessons['teached']) / $lessonCount, 2) * 100;
            $progress = $progress > 100 ? 100 : $progress;
        } else {
            $progress = 0;
        }

        return $this->render('course-manage/custom-course/index.html.twig', array(
            'course' => $course,
            'courseSet' => $courseSet,
            'lessonCount' => $lessonCount,
            'completedLessons' => $completedLessons,
            'notCompletedLessons' => $notCompletedLessons,
            'completedLessonsCount' => $completedLessonsCount,
            'notCompletedLessonsCount' => count($notCompletedLessons),
            'currentLesson' => $currentLesson,
            'progress' => $progress,
        ));
    }

    public function taskLearnDetailAction(Request $request, $courseSetId, $courseId, $taskId)
    {
        $students = array();
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);

        $count = $this->getTaskResultService()->countUsersByTaskIdAndLearnStatus($taskId, 'all');
        $paginator = new Paginator($request, $count, 20);

        $results = $this->getTaskResultService()->searchTaskResults(
            array('courseId' => $courseId, 'activityId' => $task['activityId']),
            array('time' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $teachers = $this->getCourseMemberService()->findCourseTeachers($courseId);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');

        foreach ($results as $key => $result) {
            if (in_array($result['userId'], $teacherIds)) {
                continue;
            }
            $user = $this->getUserService()->getUser($result['userId']);
            $students[$key]['nickname'] = $user['nickname'];
            $students[$key]['startTime'] = $result['createdTime'];
            $students[$key]['finishedTime'] = $result['finishedTime'];
            $students[$key]['learnTime'] = round($result['time'] / 60);
            $students[$key]['watchTime'] = round($result['watchTime'] / 60);

            if ($activity['mediaType'] == 'testpaper') {
                $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
                $paperResult = $this->getTestpaperService()->getUserFinishedResult(
                    $testpaperActivity['mediaId'],
                    $courseId,
                    $activity['id'],
                    'testpaper',
                    $user['id']
                );
                $students[$key]['result'] = empty($paperResult) ? 0 : $paperResult['score'];
            }
        }

        $task['length'] = intval($activity['length']);

        return $this->render(
            'course-manage/custom-dashboard/task-detail-modal.html.twig',
            array(
                'task' => $task,
                'paginator' => $paginator,
                'students' => $students,
                'mode' => $request->query->get('mode', 'normal'),
            )
        );
    }

    private function sortInstantCourseLessons($lessons, $currentLesson)
    {
        if (empty($currentLesson['number'])) {
            $completedLessons = $lessons;
            $notCompletedLessons = array();
        } elseif ($currentLesson['number'] == 1) {
            $completedLessons = array();
            $notCompletedLessons = $lessons;
        } else {
            foreach ($lessons as $lesson) {
                if ($lesson['number'] < $currentLesson['number']) {
                    $completedLessons[] = $lesson;
                    continue;
                }

                $notCompletedLessons[] = $lesson;
            }
        }

        return array($completedLessons, $notCompletedLessons);
    }

    public function headerAction($course, $courseSet, $termCode)
    {
        $term = $this->getCourseService()->getTermByShortCode($course['termCode']);
        return $this->render('prepare-course-manage/header.html.twig', array(
            'courseSet' => $courseSet,
            'course' => $course,
            'term' => $term,
        ));
    }

    public function sidebarAction($sideNav, $courseId, $courseSetId, $termCode)
    {
        return $this->render('prepare-course-manage/sidebar.html.twig', array(
            'side_nav' => $sideNav,
            'courseId' => $courseId,
            'courseSetId' => $courseSetId,
            'termCode' => $termCode,
        ));
    }


    public function prepareImportCoursesAction()
    {
        $user = $this->getCurrentUser();
        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($user['id']);
        $courseIds = ArrayToolkit::column($members, 'courseId');

        return $this->createJsonResponse($this->prepareImportCourses($courseIds));
    }

    public function prepareImportCourseSharesAction()
    {
        $user = $this->getCurrentUser();

        $courseShares = $this->getCourseShareService()->findCourseSharesByToUserId($user['id']);
        $courseIds = ArrayToolkit::column($courseShares, 'courseId');

        return $this->createJsonResponse($this->prepareImportCourses($courseIds));
    }

    protected function prepareImportCourses($ids)
    {
        $courses = $this->getCourseService()->sortImportCourses($ids);
        foreach ($courses as $key => $course) {
            $courses[$key]['url'] = $this->generateUrl('prepare_import_course_lessons', array('courseId'=>$course['id']));
        }

        return $courses;
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function generateDefaultCourse($courseSet)
    {
        return array(
            'courseSetId'   => $courseSet['id'],
            'title'         => '默认班级',
            'expiryMode'    => 'forever',
            'expiryDays'    => 0,
            'learnMode'     => 'freeMode',
            'isDefault'     => 1,
            'isFree'        => 1,
            'type'          => 'instant',
            'serializeMode' => $courseSet['serializeMode'],
            'status'        => 'published'
        );
    }

    public function dashboardAction(Request $request, $courseSetId, $courseId)
    {
        $tab = $request->query->get('tab', 'course');

        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
        $course = $this->getCourseService()->tryManageCourse($courseId, $courseSetId);

        switch ($tab) {
            case 'course':
                return $this->renderDashboardForCourse($course, $courseSet);
            case 'task':
                return $this->renderDashboardForTasks($course, $courseSet);
            case 'task-detail':
                return $this->renderDashboardForTaskDetails($course, $courseSet);
            default:
                throw new InvalidArgumentException("Unknown tab#{$tab}");
        }
    }

    protected function renderDashboardForCourse($course, $courseSet)
    {
        $summary = $this->getReportService()->summary($course['id']);
        if ($summary['studentNum']) {
            $summary['finishedRate'] = round($summary['finishedNum'] / $summary['studentNum'], 3) * 100;
        } else {
            $summary['finishedRate'] = 0;
        }
        $lateMonthLearndData = $this->getReportService()->getLateMonthLearnData($course['id']);

        return $this->render(
            'course-manage/custom-dashboard/course.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
                'summary' => $summary,
                'studentNum' => ArrayToolkit::column($lateMonthLearndData, 'studentNum'),
                'finishedNum' => ArrayToolkit::column($lateMonthLearndData, 'finishedNum'),
                'finishedRate' => ArrayToolkit::column($lateMonthLearndData, 'finishedRate'),
                'noteNum' => ArrayToolkit::column($lateMonthLearndData, 'noteNum'),
                'askNum' => ArrayToolkit::column($lateMonthLearndData, 'askNum'),
                'discussionNum' => ArrayToolkit::column($lateMonthLearndData, 'discussionNum'),
                'days' => ArrayToolkit::column($lateMonthLearndData, 'day'),
            )
        );
    }

    protected function renderDashboardForTasks($course, $courseSet)
    {
        $taskStat = $this->getReportService()->getCourseTaskLearnStat($course['id']);
        $taskStat = ArrayToolkit::index($taskStat, 'id');
        $lessonTasks = $this->getLessonTaskService()->findLessonTasksByCourseId($course['id']);
        $lessonTasks = ArrayToolkit::group($lessonTasks, 'lessonId');
        foreach ($lessonTasks as &$lessonTask) {
            // $lessonTask = ArrayToolkit::group($lessonTask, 'stage');
            // foreach ($lessonTask as $key => $stageTasks) {
            $taskIds = ArrayToolkit::column($lessonTask, 'taskId');
            $lessonTask = array(
                'taskRemarks' => array(),
                'taskTitles' => array(),
                'finishedRate' => array(),
                'finishedNum' => array(),
                'learnNum' => array(),
            );
            foreach ($taskIds as $num => $taskId) {
                if (empty($taskStat[$taskId])) {
                    continue;
                }
                $num++;
                $lessonTask['taskRemarks'][] = $taskStat[$taskId]['title'];
                $lessonTask['taskTitles'][] = "任务{$num}";
                $lessonTask['finishedRate'][] = $taskStat[$taskId]['finishedRate'];
                $lessonTask['finishedNum'][] = $taskStat[$taskId]['finishedNum'];
                $lessonTask['learnNum'][] = $taskStat[$taskId]['finishedNum'];
            }
        }
        // }

        return $this->render(
            'course-manage/custom-dashboard/task.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
                'lessonTasks' => $lessonTasks,
            )
        );
    }

    protected function renderDashboardForTaskDetails($course, $courseSet)
    {
        $isLearnedNum = $this->getCourseMemberService()->countMembers(
            array('isLearned' => 1, 'courseId' => $course['id'])
        );

        $noteCount = $this->getNoteService()->countCourseNotes(array('courseId' => $course['id']));

        $questionCount = $this->getThreadService()->countThreads(
            array('courseId' => $course['id'], 'type' => 'question')
        );

        return $this->render(
            'course-manage/custom-dashboard/task-learn.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
                'isLearnedNum' => $isLearnedNum,
                'noteCount' => $noteCount,
                'questionCount' => $questionCount,
            )
        );
    }

    protected function getTeachedLesson($courseId, $page)
    {
        $conditions = array(
            'courseId' => $courseId,
            'gtTaskNum' => 0,
        );

        $lessons = $this->getCourseLessonService()->searchCourseLesson(
            $conditions,
            array(),
            ($page - 1) * 10,
            10
        );

        return ArrayToolkit::index($lessons, 'id');
    }

    public function taskTableDetailAction(request $request, $courseId)
    {
        $page = $request->query->get('page');

        $course = $this->getCourseService()->tryManageCourse($courseId);

        $lessons = $this->getTeachedLesson($courseId, $page);

        $tasks = array();
        foreach ($lessons as $lesson) {
            $tasks[$lesson['id']] = $this->getLessonTasks($lesson);
        }

        return $this->render(
            'course-manage/custom-dashboard/task-table-detail.html.twig',
            array(
                'courseSet' => array('title'=>1),
                'course' => $course,
                'lessonTasks' => $tasks,
                'lessons' => $lessons,
                'stages' => $this->getStage(),
            )
        );
    }

    protected function getLessonTasks($lesson)
    {
        $tasks = $this->getTaskService()->findTasksFetchActivityBylessonId($lesson['id']);

        foreach ($tasks as $key => $value) {
            $taskLearnedNum = $this->getTaskResultService()->countLearnNumByTaskId($value['id']);

            $finishedNum = $this->getTaskResultService()->countUsersByTaskIdAndLearnStatus($value['id'], 'finish');

            $taskLearnTime = $this->getTaskResultService()->getLearnedTimeByCourseIdGroupByCourseTaskId($value['id']);
            $taskLearnTime = $taskLearnedNum == 0 ? 0 : round($taskLearnTime / $taskLearnedNum / 60);
            $taskWatchTime = $this->getTaskResultService()->getWatchTimeByCourseIdGroupByCourseTaskId($value['id']);
            $taskWatchTime = $taskLearnedNum == 0 ? 0 : round($taskWatchTime / $taskLearnedNum / 60);

            $tasks[$key]['LearnedNum'] = $taskLearnedNum;
            $tasks[$key]['length'] = round(intval($tasks[$key]['activity']['length']) / 60);
            $tasks[$key]['type'] = $tasks[$key]['activity']['mediaType'];
            $tasks[$key]['finishedNum'] = $finishedNum;
            $tasks[$key]['learnTime'] = $taskLearnTime;
            $tasks[$key]['watchTime'] = $taskWatchTime;

            if ($value['type'] == 'testpaper') {
                $testpaperActivity = $this->getTestpaperActivityService()->getActivity($value['activity']['mediaId']);

                $conditions = array(
                    'testId' => $testpaperActivity['mediaId'],
                    'type' => 'testpaper',
                    'status' => 'finished',
                    'courseId' => $value['courseId'],
                );
                $score = $this->getTestpaperService()->searchTestpapersScore($conditions);
                $paperNum = $this->getTestpaperService()->searchTestpaperResultsCount($conditions);
                $tasks[$key]['score'] = ($finishedNum == 0 || $paperNum == 0) ? 0 : intval($score / $paperNum);
            }
        }

        return $this->groupTasks($lesson['id'], $tasks);
    }

    protected function groupTasks($lessonId, $tasks)
    {
        $chapters = $this->getCourseService()->findChaptersByLessonId($lessonId);
        $chapters = ArrayToolkit::index($chapters, 'id');

        $lessonTasks = $this->getCourseLessonService()->findLessonTasksByLessonId($lessonId);
        $lessonTasks = ArrayToolkit::index($lessonTasks, 'taskId');

        $results = array('before' => array(), 'in' => array(), 'after' => array());
        foreach ($tasks as $task) {
            $type = $lessonTasks[$task['id']]['stage'];
            $seq = $chapters[$task['categoryId']]['seq'];
            $results[$type][$seq] = $task;
        }
        $results = array_filter($results);

        return $results;
    }

    protected function getStage()
    {
        return array(
            'before' => '课前',
            'in' => '课中',
            'after' => '课后',
        );
    }

    public function checkCourseAction(Request $request, $courseId)
    {
        if ($this->getCourseService()->isAnyLessonStart($courseId)) {
            return $this->createJsonResponse(array(
                'status' => 'failed',
                'data' => array()
            ));
        }

        $url = $this->generateUrl('custom_lesson_show', array(
            'courseId' => $courseId,
            'lessonId' => $request->query->get('lessonId')
        ));
        return $this->createJsonResponse(array(
            'status' => 'success',
            'data' => array(
                'url' => $url
            )
        ));
    }

    protected function getGroupMemberSerice()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getWebExtension()
    {
        return $this->container->get('web.twig.extension');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getCourseShareService()
    {
        return $this->createService('CustomBundle:Course:CourseShareService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->createService('CustomBundle:Activity:ActivityService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    protected function getReportService()
    {
        return $this->createService('CustomBundle:Course:ReportService');
    }

    protected function getNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    protected function getThreadService()
    {
        return $this->createService('Course:ThreadService');
    }

    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    protected function getMarkerReportService()
    {
        return $this->createService('Marker:ReportService');
    }

    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    protected function getLessonTaskService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
