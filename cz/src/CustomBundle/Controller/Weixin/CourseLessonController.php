<?php
namespace CustomBundle\Controller\Weixin;

use CustomBundle\Biz\Course\Service;
use CustomBundle\Common\WeixinClient;
use AppBundle\Common\ArrayToolkit;
use Biz\User\Service\TokenService;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Controller\Weixin\WeixinBaseController;

class CourseLessonController extends WeixinBaseController
{
    public function completeLessonsAction(Request $request, $courseId)
    {
        $user = $this->getCurrentUser();

        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $lesson = $this->getCourseLessonService()->getCurrenTeachCourseLesson($courseId);

        $currentTask = array();
        if (!empty($lesson) && $lesson['status'] == 'teaching') {
            $lessonItems = $this->getCourseLessonService()->findCourseLessonItems($lesson['id']);
            $currentTask = $this->currentTask($lessonItems['in'], $courseSet['teacherIds']);
            $currentTask['lessonTitle'] = empty($lesson['title']) ? '课次'.$lesson['number'] : $lesson['title'];
            $currentTask['lessonId'] = $lesson['id'];
        }
        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        $cover = '';
        if (!empty($courseSet['cover'])) {
            $cover = $this->getWebExtension()->getFilePath($courseSet['cover']['middle'], '');
        }
        $lessons = $this->getCourseLessonService()->findCompleteLessonByCourseId($courseId);

        return $this->createJsonResponse(array(
            'role' => $member['role'],
            'courseSetTitle' => $courseSet['title'],
            'courseTitle' => $course['title'],
            'cover' => $cover,
            'lessons' => $this->sortLessons($course['id'], $lessons),
            'currentTask' => $currentTask
        ));
    }

    public function concisionLessonsAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $cover = '';
        if (!empty($courseSet['cover'])) {
            $cover = $this->getWebExtension()->getFilePath($courseSet['cover']['middle'], '');
        }
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

        return $this->createJsonResponse(array(
            'courseSetTitle' => $courseSet['title'],
            'courseTitle' => $course['title'],
            'cover' => $cover,
            'lessons' => $this->sortLessons($courseId, $lessons),
        ));
    }

    public function sortLessons($courseId, $lessons)
    {
        $user = $this->getCurrentUser();
        $evaluations = $this->getLessonEvaluationService()->findEvaluationsByCourseIdAndUserId($courseId, $user['id']);
        $evaluations = ArrayToolkit::index($evaluations, 'lessonId');
        $finishedLessons = array();
        $unFinishedLessons = array();
        foreach ($lessons as $lesson) {
            $concisionLesson = array(
                'id' => $lesson['id'],
                'title' => $lesson['title'],
                'number' => $lesson['number'],
                'status' => $lesson['status'],
                'isEvaluation' => isset($evaluations[$lesson['id']]),
            );

            if ($lesson['status'] == 'teached') {
                if (!empty($unFinishedLessons)) {
                    $finishedLessons = array_merge($finishedLessons, $unFinishedLessons);
                    $unFinishedLessons = array();
                }
                $finishedLessons[] = $concisionLesson;
            } elseif ($lesson['status'] == 'teaching') {
                if (!empty($unFinishedLessons)) {
                    $finishedLessons = array_merge($finishedLessons, $unFinishedLessons);
                    $unFinishedLessons = array();
                }
                $unFinishedLessons[] = $concisionLesson;
            } else {
                $unFinishedLessons[] = $concisionLesson;
            }
        }

        return array_merge($unFinishedLessons, $finishedLessons);
    }

    public function courseLessonStudy(Request $request, $lessonId)
    {
        $user = $this->getCurrentUser();
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $signIn = $this->getSignInService()->getLastSignInByLessonId($lesson['id']);
        $lessonStages = $this->findLessonTaskStages($lesson['id']);

        $currentTask = $this->getTaskService()->getCurrentTaskByCourseIdAndUserIds($courseId, $courseSet['teacherIds']);

        $result = array(
            'lessonId' => $lesson['id'],
            'courseSetTitle' => $courseSet['title'],
            'courseTitle' => $course['title'],
            'cover' => empty($courseSet['cover']['large']) ? null : $this->getWebExtension()->getFilePath($courseSet['cover']['large'], ''),
            'lessonStatus' => $lesson['status'],
            'termCode' => $course['termCode'],
            'lessonTitle' => $lesson['title'],
            'lessonNumber' => $lesson['number'],
            'before' => array_values($lessonStages['before']),
            'in' => array_values($lessonStages['in']),
            'after' => array_values($lessonStages['after']),
            'signIn' => array('status' => $signIn['status'], 'time' => empty($signIn['time']) ? 0 : $signIn['time']),
            'currentTask' => $currentTask
        );

        return $this->createJsonResponse($result);
    }

    public function lessonEndAction(Request $request)
    {
        $data = $request->query->all();
        $user = $this->getCurrentUser();
        $lessonId = $data['lessonId'];
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        if (!$this->getCourseMemberService()->isCourseTeacher($lesson['courseId'], $user['id'])) {
            return $this->createJsonResponse(array(
                'message' => '权限不够',
            ));
        }

        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $this->getCourseLessonService()->endCourseLesson($lessonId);

        $this->weixinLessonEndMessage($request, $lessonId);

        return $this->createJsonResponse(true);
    }

    public function weixinSendMessageAction(Request $request, $courseId, $lessonId)
    {
        $type = $request->query->get('type');
        $user = $this->getCurrentUser();
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        if (!$this->getCourseMemberService()->isCourseTeacher($lesson['courseId'], $user['id'])) {
            return $this->createJsonResponse(array(
                'message' => '权限不够',
            ));
        }

        $this->weixinSendMessage($request, $lesson, $this->types($type));

        return $this->createJsonResponse(true);
    }

    protected function weixinLessonEndMessage($request, $lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $courseId = $lesson['courseId'];
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $goto = urlencode("/weixin/index.html#/course/{$courseId}/lesson");
        $studentEvaluationPost = array(
            'title' => '请对本次课给予评价',
            'description' => "《{$courseSet['title']}》，课次{$lesson['number']}，已下课，点击给予评价",
            'url' => $this->generateUrl('weixin_redirect', array("goto" => $goto), true),
            'picurl' => '',
        );
        $studentReportPost = array(
            'title' => '课堂报告已生成，请及时查看',
            'description' => "《{$courseSet['title']}》，课次{$lesson['number']}，课程报告已生成，请点击查看详情",
            'url' => $this->generateUrl('instant_course_student_class_report', array('courseId' => $courseId, 'lessonId' => $lessonId), true),
            'picurl' => '',
        );
        $teacherReportPost = array(
            'title' => '课堂报告已生成，请及时查看',
            'description' => "《{$courseSet['title']}》，课次{$lesson['number']}，课程报告已生成，请点击查看详情",
            'url' => $this->generateUrl('instant_course_teacher_class_report', array('courseId' => $courseId, 'lessonId' => $lessonId), true),
            'picurl' => '',
        );

        $students = $this->getCourseMemberService()->findCourseStudentsWithUserInfo($courseId);
        $students = ArrayToolkit::column($students, 'number');

        $teachers = $this->getCourseMemberService()->findCourseTeachers($courseId);
        $teacherIds = ArrayToolkit::column($teachers, 'id');
        $teachers = $this->getCourseMemberService()->findMembersByIdsWithUserInfo($teacherIds, true);
        $teachers = ArrayToolkit::column($teachers, 'number');

        $client = $this->getPlatformClient();
        $client->sendNewsMessage($students, $studentEvaluationPost);
        $client->sendNewsMessage($students, $studentReportPost);
        $client->sendNewsMessage($teachers, $teacherReportPost);
    }

    protected function weixinSendMessage($lesson, $type)
    {
        $goto = urlencode("/weixin/index.html#/course/{$lesson['courseId']}/study");
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $studentMessagePost = array(
            'title' => "你有一份{$type}作业，请及时查看",
            'description' => "《{$courseSet['title']}》，课次{$lesson['number']}，{$type}作业，请点击查看详情",
            'url' => $this->generateUrl('weixin_redirect', array("goto" => $goto), true),
            'picurl' => '',
        );

        $students = $this->getCourseMemberService()->findCourseStudentsWithUserInfo($lesson['courseId']);
        $students = ArrayToolkit::column($students, 'number');

        $client = $this->getPlatformClient();
        $client->sendNewsMessage($students, $studentMessagePost);
    }

    protected function findLessonTaskStages($lessonId)
    {
        $user = $this->getCurrentUser();
        $lesson = $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $lessonTasks = $this->getCourseLessonService()->findLessonTasksByLessonId($lessonId);

        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');
        $tasks = $this->getTaskService()->findTasksByIds($taskIds);
        $taskResults = $this->getTaskResultService()->findTaskResultsByTaskIdsAndUserId($taskIds, $user['id']);
        $teachers = $this->getCourseMemberService()->findCourseTeachers($lesson['courseId']);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');
        $teacherTaskResults = $this->getTaskService()->findResultsByTaskIdsAndUserIds($taskIds, $teacherIds);

        $evaluations = $this->getLessonEvaluationService()->findEvaluationsByLessonId($lessonId);
        $chapters = $this->getCourseLessonService()->findCourseChaptersByLessonId($lessonId);
        $isCourseTeacher = $this->getCourseMemberService()->isCourseTeacher($lesson['courseId'], $user['id']);

        $cpLesson = array(
            'isEvaluation' => isset($evaluations[$lesson['id']]),
            'isShowPhase' => $lesson['status'] != 'teached',
            'number' => $lesson['number'],
            'status' => $lesson['status'],
            'title' => empty($lesson['title']) ? '课次'.$lesson['number'] : $lesson['title'],
            'id' => $lesson['id'],
        );
        $data = array(
            'lesson' => $lesson,
            'chapters' => $chapters,
            'tasks' => $tasks,
            'taskResults' => $taskResults,
            'teacherTaskResults' => $teacherTaskResults,
            'isCourseTeacher' => $isCourseTeacher,
        );

        return $this->getCourseLessonService()->lessonTaskSort($data);
    }

    protected function getLessonEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function types($type)
    {
        $types = array(
            'before' => '课前',
            'after' => '课后',
        );

        return isset($types[$type]) ? $types[$type] : "";
    }
}
