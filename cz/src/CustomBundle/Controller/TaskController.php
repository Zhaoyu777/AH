<?php

namespace CustomBundle\Controller;

use CustomBundle\Common\Platform\PlatformFactory;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\CurlToolkit;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException as ServiceAccessDeniedException;

class TaskController extends BaseController
{
    public function lessonShowAction($courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        if ($course['status'] == 'delete') {
            return $this->createMessageResponse('info', '该课程已删除', null, 3000, $this->generateUrl('my_teaching_instant_courses'));
        }
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);

        $task = $this->getTaskService()->getFirstInClassTaskByLessonId($lessonId);

        if (empty($task)) {
            return $this->createMessageResponse('error', '该课次没有设置课上内容！', '', 3, $this->generateUrl('custom_course_lesson_list', array('courseId' => $courseId)));
        }

        $record = $this->getLessonRecordService()->getByLessonId($lessonId);
        if (!empty($record)) {
            $taskId = $record['taskId'];
        } else {
            $taskId = $task['id'];
        }

        return $this->forward('CustomBundle:Task:show', array(
            'lessonId' => $lessonId,
            'id' => $taskId,
            'courseId' => $courseId,
        ));
    }

    public function detailAction()
    {
        return $this->render('activity/display-wall/detail.html.twig');
    }

    public function courseLearnAction($courseId, $nextLessonId)
    {
        $lesson = $this->getCourseLessonService()->getTeachingCourseLessonByCourseId($courseId);

        if (empty($lesson)) {
            $lessonId = $nextLessonId;
        } else {
            $lessonId = $lesson['id'];
        }

        return $this->redirect($this->generateUrl('instant_lesson_learn', array(
            'courseId' => $courseId,
            'lessonId' => $lessonId,
        )));
    }

    public function lessonLearnAction(Request $request, $courseId, $lessonId)
    {
        // $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        /*if ($lesson['status'] == 'teaching') {
            $teachers = $this->getCourseMemberService()->findCourseTeachers($courseId);
            $teacherIds = ArrayToolkit::column($teachers, 'userId');

            $taskResult = $this->getTaskService()->getLatestTaskResultByCourseIdAndUserIds($courseId, $teacherIds);
            $taskId = $taskResult['courseTaskId'];
        } else*/
        $stage = $request->query->get('stage');
        if ($stage && $stage == 'in') {
            $inClassChapter = $this->getCourseService()->getFirstInClassTaskChapterByLessonId($lessonId);
            $task = empty($inClassChapter) ? null:$this->getTaskService()->getTaskByCategoryId($inClassChapter['id']);
        } elseif ($stage && $stage == 'before') {
            $beforeClassChapter = $this->getCourseService()->getFirstBeforeClassTaskChapterByLessonId($lessonId);
            $task = empty($beforeClassChapter) ? null:$this->getTaskService()->getTaskByCategoryId($beforeClassChapter['id']);

        } else {
            $task = $this->getTaskService()->getCurrentTaskByLessonId($lessonId);
        }
        if (empty($task)) {
            $taskId = 0;
        } else {
            $taskId = $task['id'];
        }

        return $this->forward('CustomBundle:Task:show', array(
            'lessonId' => $lessonId,
            'id' => $taskId,
            'courseId' => $courseId,
        ));
    }

    public function lessonEndAction(Request $request, $courseId, $lessonId)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $this->getCourseLessonService()->endCourseLesson($lessonId);

        $this->sendEndLessonMessage($request, $lessonId);

        return $this->createJsonResponse($this->generateUrl('custom_after_class_show', array('courseId' => $courseId, 'lessonId' => $lessonId)));
    }

    public function afterClassShowAction(Request $request, $courseId, $lessonId)
    {
        return $this->render('custom-task/after-class/show.html.twig');
    }

    public function lessonCancelAction($courseId, $lessonId)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $this->getCourseLessonService()->cancelCourseLesson($lessonId);

        return $this->createJsonResponse($this->generateUrl('custom_course_lesson_list', array('courseId' => $courseId)));
    }

    public function showAction(Request $request, $courseId, $lessonId, $id)
    {
        $lesson = $this->getCourseLessonService()->tryManageCourseLesson($lessonId);

        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return $this->createMessageResponse('info', '请先登录', '', 3, $this->generateUrl('login'));
        }

        $preview = $request->query->get('preview');
        if (!empty($id)) {
            $task = $this->tryLearnTask($courseId, $id, (bool) $preview);
        } else {
            $task = array(
                'id' => $id,
                'courseId' => $courseId,
            );
        }

        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        if ($member['locked']) {
            return $this->redirectToRoute('my_course_show', array('id' => $courseId));
        }

        if ($course['expiryMode'] == 'date' && $course['expiryStartDate'] >= time()) {
            return $this->redirectToRoute('course_show', array('id' => $courseId));
        }

        if ($member && !$this->getCourseMemberService()->isMemberNonExpired($course, $member)) {
            return $this->redirect($this->generateUrl('my_course_show', array('id' => $courseId)));
        }

        if (!empty($id)) {
            if ($this->canStartTask($task)) {
                $this->getActivityService()->trigger(
                    $task['activityId'],
                    'start',
                    array(
                        'task' => $task,
                    )
                );
            }

            $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($id);
            if (empty($taskResult)) {
                $taskResult = array('status' => 'none');
            }

            if ($taskResult['status'] == 'finish') {
                list($course, $nextTask, $finishedRate) = $this->getNextTaskAndFinishedRate($task);
            }

            $this->freshTaskLearnStat($request, $task['id']);
        }

        $signin = $this->getSignInService()->getLastSignInByLessonId($lessonId);

        return $this->render(
            'custom-task/show.html.twig',
            array(
                'code' => $signin['status'] == 'start' ? $signin['verifyCode'] : '',
                'count' => $this->getTaskService()->countFrontTaskByTaskId($id),
                'course' => $course,
                'courseSet' => $courseSet,
                //'lessonId' => $lessonId,
                'lesson' => $lesson,
                'task' => $task,
                'preview' => $preview,
                'taskResult' => empty($taskResult) ? array() : $taskResult,
                'nextTask' => empty($nextTask) ? array() : $nextTask,
                'finishedRate' => empty($finishedRate) ? 0 : $finishedRate,
                'site' => $this->getSettingService()->get('site', array()),
            )
        );
    }

    public function taskPluginsAction(Request $request, $courseId, $lessonId, $taskId)
    {
        $preview = $request->query->get('preview', false);
        $user = $this->getCurrentUser();

        if (!empty($taskId)) {
            $this->tryLearnTask($courseId, $taskId);
        }

        $plugins = array(array(
            'code' => 'task-list',
            'name' => '任务',
            'icon' => 'cz-icon cz-icon-mission',
            'url' => $this->generateUrl(
                'lesson_task_show_plugin_task_list',
                array(
                    'courseId' => $courseId,
                    'lessonId' => $lessonId,
                    'taskId' => $taskId,
                    'preview' => $preview,
                )
            ),
        ));

        $courseMember = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);
        if ($courseMember['role'] == 'student') {
            $plugins[] = array(
                'code' => 'sign-list',
                'name' => '签到',
                'icon' => 'cz-icon cz-icon-Signin',
                'url' => $this->generateUrl(
                    'custom_sign_in_record',
                    array(
                        'courseId' => $courseId,
                        'lessonId' => $lessonId,
                        'userId' => $user['id'],
                        'preview' => $preview,
                    )
                ),
            );
        } else {
            $plugins[] = array(
                'code' => 'sign-list',
                'name' => '签到',
                'icon' => 'cz-icon cz-icon-Signin',
                'url' => $this->generateUrl(
                    'custom_sign_in_manage',
                    array(
                        'courseId' => $courseId,
                        'lessonId' => $lessonId,
                        'userId' => $user['id'],
                        'preview' => $preview,
                    )
                ),
            );
        }

        return $this->createJsonResponse($plugins);
    }

    public function sortAction(Request $request, $courseId, $lessonId)
    {
        $ids = $request->request->get('ids');
        $this->getCourseService()->sortLessonItems($courseId, $lessonId, $ids);

        return $this->createJsonResponse(true);
    }

    protected function getNextTaskAndFinishedRate($task)
    {
        $nextTask = $this->getTaskService()->getNextTask($task['id']);
        $course = $this->getCourseService()->getCourse($task['courseId']);
        $user = $this->getUser();
        $conditions = array(
            'courseId' => $task['courseId'],
            'userId' => $user['id'],
            'status' => 'finish',
        );
        $finishedCount = $this->getTaskResultService()->countTaskResults($conditions);

        $finishedRate = $this->calcuteProgress($finishedCount, $course);

        return array($course, $nextTask, $finishedRate);
    }

    protected function tryLearnTask($courseId, $taskId, $preview = false)
    {
        if ($preview) {
            if ($this->getCourseService()->hasCourseManagerRole($courseId)) {
                $task = $this->getTaskService()->getTask($taskId);
            } else {
                throw $this->createNotFoundException('you can not preview this task ');
            }
        } else {
            $task = $this->getTaskService()->tryTakeTask($taskId);
        }
        if (empty($task)) {
            throw $this->createNotFoundException(sprintf('task not found #%d', $taskId));
        }

        if ($task['courseId'] != $courseId) {
            throw $this->createAccessDeniedException();
        }

        return $task;
    }

    protected function calcuteProgress($finishedCount, $course)
    {
        $progress = 0;
        if (empty($course['compulsoryTaskNum'])) {
            return $progress;
        }
        $progress = intval($finishedCount / $course['compulsoryTaskNum'] * 100);

        return $progress > 100 ? 100 : $progress;
    }

    private function canStartTask($task)
    {
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        if (($lesson['status'] == 'created') && ($lessonTask['stage'] != 'before')) {
            return false;
        }

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig($activity['mediaType']);

        return $config->allowTaskAutoStart($activity);
    }

    private function freshTaskLearnStat(Request $request, $taskId)
    {
        $key = 'task.'.$taskId;
        $session = $request->getSession();
        $taskStore = $session->get($key, array());
        $taskStore['start'] = time();
        $taskStore['lastTriggerTime'] = 0;

        $session->set($key, $taskStore);
    }

    public function randStudentAction(Request $request, $courseId, $taskId)
    {
        $results = $this->getRollcallResultService()->findResultsByTaskId($taskId);
        $clearUserIds = ArrayToolkit::column($results, 'userId');

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);

        $randomStudentIds = $this->getCourseMemberService()->findRandomStudentIdsByLessonId($lessonTask['lessonId'], $clearUserIds, 20);

        if (empty($randomStudentIds)) {
            return $this->createJsonResponse(array('success'=>'error', 'message' =>'所有成员已被抽过。'));
        }
        $selectedStudentId = $randomStudentIds[array_rand($randomStudentIds, 1)];
        $randomStudents = $this->baseUserInfo($randomStudentIds);
        $selectedStudent = $randomStudents[$selectedStudentId];

        $result = $this->createRollcallResult($taskId, $selectedStudent, array_values($randomStudents));

        if (!empty($result)) {
            $Course = $this->getCourseService()->getCourse($courseId);
            $CourseSet = $this->getCourseSetService()->getCourseSet($Course['courseSetId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
            $url = "/weixin/index.html#/course/{$courseId}/lesson/{$lessonTask['lessonId']}/task/{$taskId}/activity/{$result['activityId']}/type/rollcall";
            $toid = $selectedStudent['number'];
            $this->sendRollcallMessage($request, $toid, $CourseSet['title'], $url);
        }

        return $this->createJsonResponse(true);
    }

    protected function createRollcallResult($taskId, $selectUser, $randomStudents)
    {
        $task = $this->getTaskService()->getTask($taskId);

        return $this->getRollcallResultService()->createResult(
            array(
                'courseId' => $task['courseId'],
                'activityId' => $task['activityId'],
                'userId' => $selectUser['userId'],
                'courseTaskId' => $task['id'],
                'selectUser' => $selectUser,
                'students' => $randomStudents,
            )
        );
    }

    public function taskActivityAction(Request $request, $courseId, $id)
    {
        $doAgain = $request->query->get('doAgain', false);
        $preview = $request->query->get('preview', 0);
        $task = $this->tryLearnTask($courseId, $id, $preview);

        if ($this->canStartTask($task)) {
            $this->getActivityService()->trigger(
                $task['activityId'],
                'start',
                array(
                    'task' => $task,
                )
            );
        }

        if (empty($preview) && $task['status'] != 'published') {
            return $this->render('task/inform.html.twig');
        }

        $this->freshTaskLearnStat($request, $task['id']);

        return $this->forward(
            'CustomBundle:Activity/Activity:show',
            array(
                'task' => $task,
                'preview' => $preview,
                'mode' => $request->query->get('mode', 'show'),
                'doAgain' => $doAgain
            )
        );
    }

    public function startAction($courseId, $lessonId, $taskId)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $task = $this->getTaskService()->startInstantCourseTask($taskId);

        return $this->createJsonResponse(true);
    }

    public function endAction($courseId, $lessonId, $taskId)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $task = $this->getStatusService()->endTask($taskId);

        return $this->createJsonResponse(true);
    }

    public function previewAction(Request $request, $courseId, $lessonId, $id)
    {
        $site = $this->getSettingService()->get('site', array());
        $preview = $request->query->get('preview');
        $mode = $request->query->get('mode');

        $user = $this->getUser();
        if (!$user->isLogin()) {
            return $this->createMessageResponse('info', '请先登录', '', 3, $this->generateUrl('login'));
        }

        $task = $this->tryLearnTask($courseId, $id, (bool) $preview);

        $user = $this->getCurrentUser();
        $course = $this->getCourseService()->getCourse($courseId);
        if ($course['status'] == 'delete') {
            return $this->createMessageResponse('info', '该课程已删除', null, 3000, $this->generateUrl('my_teaching_instant_courses'));
        }

        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        if ($member['locked']) {
            return $this->redirectToRoute('my_course_show', array('id' => $courseId));
        }

        if ($course['expiryMode'] == 'date' && $course['expiryStartDate'] >= time()) {
            return $this->redirectToRoute('course_show', array('id' => $courseId));
        }

        if ($member && !$this->getCourseMemberService()->isMemberNonExpired($course, $member)) {
            return $this->redirect($this->generateUrl('my_course_show', array('id' => $courseId)));
        }

        if ($this->canStartTask($task)) {
            $this->getActivityService()->trigger(
                $task['activityId'],
                'start',
                array(
                    'task' => $task,
                )
            );
        }

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($id);
        if (empty($taskResult)) {
            $taskResult = array('status' => 'none');
        }

        if ($taskResult['status'] == 'finish') {
            list($course, $nextTask, $finishedRate) = $this->getNextTaskAndFinishedRate($task);
        }

        $this->freshTaskLearnStat($request, $task['id']);
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);

        return $this->render(
            'custom-task/preview.html.twig',
            array(
                'course' => $course,
                'activity' => $activity,
                'lessonId' => $lessonId,
                'task' => $task,
                'count' => $this->getTaskService()->countFrontTaskByTaskId($id),
                'mode' => $mode,
                'preview' => $preview,
                'taskResult' => $taskResult,
                'nextTask' => empty($nextTask) ? array() : $nextTask,
                'finishedRate' => empty($finishedRate) ? 0 : $finishedRate,
                'site' => $site,
            )
        );
    }

    public function contentPreviewAction($courseId, $id)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $task = $this->getTaskService()->getTask($id);

        if (empty($task) || $task['courseId'] != $courseId) {
            throw $this->createNotFoundException('task is not exist');
        }

        return $this->forward('AppBundle:Activity/Activity:preview', array('task' => $task));
    }

    public function lessonPreviewAction(Request $request, $courseId, $lessonId)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);

        $task = $this->getTaskService()->getFirstClassTaskByLessonId($lessonId);

        if (empty($task)) {
            return $this->createMessageResponse('error', '该课次没有设置课上内容,不能预览！', '', 3, $this->generateUrl('custom_lesson_tasks', array('lessonId' => $lessonId)));
        }

        return $this->redirect($this->generateUrl('lesson_task_preview', array(
            'courseId' => $courseId,
            'lessonId' => $lessonId,
            'id' => $task['id'],
            'mode' => $request->query->get('mode'),
            'preview' => 1,
        )));
    }

    public function recordTeachingTaskAction(Request $request, $courseId, $lessonId, $taskId)
    {
        $user = $this->getCurrentUser();

        $record = $this->getLessonRecordService()->getByLessonId($lessonId);

        $result = $this->getLessonRecordService()->update($record['id'], array(
            'courseId' => $courseId,
            'taskId' => $taskId,
            'userId' => $user['id']
        ));

        return $this->createJsonResponse($result);
    }

    protected function baseUserInfo($userIds)
    {
        $users = $this->getUserService()->findUsersByIds($userIds);

        $result = array();
        foreach ($users as $key => $user) {
            $result[$user['id']] = array(
                'userId' => $user['id'],
                'truename' => $user['truename'],
                'nickname' => $user['nickname'],
                'number' => $user['number'],
                'avatar' => $this->getWebExtension()->getFpath($user['smallAvatar'], 'avatar.png')
            );
        }

        return $result;
    }

    protected function sendRollcallMessage($request, $toid, $courseSetTitle, $url)
    {
        $toids[] = $toid;
        $content = "课程《{$courseSetTitle}》正在进行点名答题,你以被老师点到，请准备作答!";
        $goto = urlencode($url);

        $articles = array(
            'title' => '有一个点名答题正在进行',
            'description' => $content,
            'url' => $this->generateUrl('weixin_redirect', array("goto" => $goto), true),
            'picurl' => '',
        );

        $client = $this->getPlatformClient();
        $client->sendNewsMessage($toids, $articles);
    }

    protected function sendEndLessonMessage($request, $lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $courseId = $lesson['courseId'];
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $goto = urlencode("/weixin/index.html#/course/{$courseId}/student-lesson");
        $studentEvaluationPost = array(
            'title' => '请对本次课给予评价',
            'description' => "《{$courseSet['title']}》，课次{$lesson['number']}，已下课，点击给予评价",
            'url' => $this->generateUrl('weixin_redirect', array("goto" => $goto), true),
            'picurl' => '',
        );

        $teacherReportPost = array(
            'title' => '课堂报告已生成，请及时查看',
            'description' => "《{$courseSet['title']}》，课次{$lesson['number']}，课程报告已生成，请点击查看详情",
            'url' => $this->generateUrl('instant_course_teacher_class_report', array('courseId' => $courseId, 'lessonId' => $lessonId), true),
            'picurl' => '',
        );

        $students = $this->getCourseMemberService()->findCourseStudentsWithUserInfo($courseId);
        $studentNumbers = ArrayToolkit::column($students, 'number');

        $teachers = $this->getCourseMemberService()->findCourseTeachers($courseId);
        $teacherIds = ArrayToolkit::column($teachers, 'id');
        $teachers = $this->getCourseMemberService()->findMembersByIdsWithUserInfo($teacherIds, true);
        $teachers = ArrayToolkit::column($teachers, 'number');

        $client = $this->getPlatformClient();
        $client->sendNewsMessage($studentNumbers, $studentEvaluationPost);
        $this->studentMessage($courseSet, $lesson, $students);
        $client->sendNewsMessage($teachers, $teacherReportPost);
    }

    protected function studentMessage($courseSet, $lesson, $students)
    {
        $message = array(
            'title' => '课堂报告已生成，请及时查看',
            'description' => "《{$courseSet['title']}》，课次{$lesson['number']}，课程报告已生成，请点击查看详情",
            'url' => "",
            'picurl' => '',
        );
        $client = $this->getPlatformClient();

        foreach ($students as $student) {
            $message['url'] = $this->generateUrl('custom_courser_lesson_student_custom_report', array('courseId' => $lesson['courseId'], 'lessonId' => $lesson['id'], 'userId' => $student['userId']), true);
            $client->sendNewsMessage(array($student['number']), $message);
        }
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getRollcallResultService()
    {
        return $this->createService('CustomBundle:Activity:RollcallResultService');
    }

    protected function getPlatformClient()
    {
        $biz = $this->getBiz();

        return PlatformFactory::create($biz);
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function getLessonRecordService()
    {
        return $this->createService('CustomBundle:Lesson:RecordService');
    }
}
