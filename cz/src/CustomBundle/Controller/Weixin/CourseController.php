<?php
namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\MaterialService;
use CustomBundle\Biz\Course\Service\MemberService;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Controller\Weixin\WeixinBaseController;

class CourseController extends WeixinBaseController
{
    public function ka()
    {
        $body = array(
        'addr' => "0x87b7AA596FD8a3F2929b3E4d5A39340C00d2020E",
        );

        $url = "http://www.gems.place/promote/user/601";
        $result = CurlToolkit::request('PUT', $url, $body, array());
    }

    public function learningCoursesAction(Request $request)
    {
        $this->ka();
        exit();
        $request = $request->query->all();
        $user = $this->getCurrentUser();
        if (empty($user)) {
            return $this->createJsonResponse(array(
                'message' => '未登录',
            ));
        }

        $members = $this->getCourseMemberService()->findStudentMemberByUserId($user['id']);
        $courses = $this->currentTermCourses($members, $user);

        return $this->createJsonResponse($courses);
    }

    public function teachingCoursesAction(Request $request)
    {
        $request = $request->query->all();
        $user = $this->getCurrentUser();

        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($user['id']);
        $courses = $this->currentTermCourses($members, $user);

        return $this->createJsonResponse($courses);
    }

    public function myTeachingAction(Request $request)
    {
        $request = $request->query->all();
        $user = $this->getCurrentUser();

        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($user['id']);
        $courseIds = ArrayToolkit::column($members, 'courseId');
        $courses = $this->sortWeixinCourses($courseIds);

        return $this->createJsonResponse($courses);
    }

    public function myLearningAction(Request $request)
    {
        $request = $request->query->all();
        $user = $this->getCurrentUser();

        $members = $this->getCourseMemberService()->findStudentMemberByUserId($user['id']);
        $courseIds = ArrayToolkit::column($members, 'courseId');
        $courses = $this->sortWeixinCourses($courseIds);

        return $this->createJsonResponse($courses);
    }

    protected function sortWeixinCourses($courseIds)
    {
        $courses = $this->getCourseService()->findNormalCoursesByIds($courseIds);
        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        $result = array();
        foreach ($courses as $course) {
            $result[] = array(
                'id' => $course['id'],
                'courseTitle' => $course['title'],
                'cover' => empty($courseSets[$course['courseSetId']]['cover']['middle']) ? null : $this->getWebExtension()->getFilePath($courseSets[$course['courseSetId']]['cover']['middle'], ''),
                'title' => $courseSets[$course['courseSetId']]['title'],
            );
        }

        return $result;
    }

    public function courseStudyAction(Request $request, $courseId)
    {
        $user = $this->getCurrentUser();
        $lessonId = $request->query->get('lessonId');

        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        if ($lessonId) {
            $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        } else {
            $lesson = $this->getCourseLessonService()->getStudyLessonByCourseId($courseId);
        }

        $signIn = $this->getSignInService()->getLastSignInByLessonId($lesson['id']);

        $lessonStages = $this->getCourseLessonService()->findLessonTaskStagesByLessonId($lesson['id']);

        $currentTask = $this->getTaskService()->getCurrentTaskByCourseIdAndUserIds($courseId, $courseSet['teacherIds']);

        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        return $this->createJsonResponse(array(
            'role' => $member['role'],
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
        ));
    }

    public function currentCourseRoleAction($courseId)
    {
        $user = $this->getCurrentUser();
        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        $result = array(
            'courseId' => $courseId,
            'role' => $member['role']
        );
        return $this->createJsonResponse($result);
    }

    protected function currentTermCourses($members, $user)
    {
        $user = $this->getCurrentUser();
        // $currentTerm = $this->getCourseService()->getCurrentTerm();

        $courseIds = ArrayToolkit::column($members, 'courseId');
        // $courses = $this->getCourseService()->findInstantCoursesByIdsAndTermCode($courseIds, $currentTerm['shortCode']);
        $courses = $this->getCourseService()->findInstantCoursesByIds($courseIds);

        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        $result = array(
            'courses' => array()
        );

        if (!empty($courses)) {
            foreach ($courses as $key => $course) {
                $courseId = $course['id'];
                $result['courses'][] = array(
                    'id' => $courseId,
                    'courseTitle' => $course['title'],
                    'cover' => empty($courseSets[$course['courseSetId']]['cover']['middle']) ? null : $this->getWebExtension()->getFilePath($courseSets[$course['courseSetId']]['cover']['middle'], ''),
                    'courseTitle' => $course['title'],
                    'courseSetTitle' => $courseSets[$course['courseSetId']]['title'],
                    'isLesson' => $user->isTeacher() ? $this->teacherIsLesson($courseId) : null,
                    'taskNum' => !$user->isTeacher() ? $this->unfinishedTaskNum($courseId) : null,
                    'lessonStatus' => $this->getCourseLessonService()->getCourseLessonStatus($courseId),
                );
            }
        }

        return $result;
    }

    protected function unfinishedTaskNum($courseId)
    {
        $user = $this->getCurrentUser();
        $currenLesson = $this->getCourseLessonService()->getCurrenTeachCourseLesson($courseId);
        $teachedLessons = $this->getCourseLessonService()->findCourseLessonsByCourseIdAndStatus($courseId, 'teached');
        $onLesson = array_pop($teachedLessons);

        $taskIds = $this->lessonTaskIds($currenLesson, $onLesson);

        $conditions = array(
            'courseTaskIds' => $taskIds,
            'userId' => $user['id'],
            'status' => 'finish',
        );
        $finishCount = $this->getTaskResultService()->countTaskResults($conditions);

        return count($taskIds)-$finishCount;
    }

    protected function lessonTaskIds($currenLesson, $onLesson)
    {
        $tasks = array();
        if (!empty($onLesson['id'])) {
            $tasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($onLesson['id'], 'after');
        }
        if (!empty($currenLesson['id'])) {
            $beforeTasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($currenLesson['id'], 'before');
            $tasks = array_merge($tasks, $beforeTasks);
        }

        return ArrayToolkit::column($tasks, 'taskId');
    }

    protected function teacherIsLesson($courseId)
    {
        $lesson = $this->getCourseLessonService()->getCurrenTeachCourseLesson($courseId);

        if (!empty($lesson)) {
            $lessonItems = $this->getTaskService()->getFirstClassTaskByLessonId($lesson['id']);
            $taskNum = 1;
            if (empty($lessonItems)) {
                return 0;
            }
        }

        return 1;
    }

    protected function currentLesson($courseId)
    {
        $lesson = $this->getCourseLessonService()->getTeachingCourseLessonByCourseId($courseId);

        if (empty($lesson)) {
            $lesson = $this->getCourseLessonService()->getCurrenTeachCourseLesson($courseId);
        }

        if (empty($lesson)) {
            $lesson = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);
            $lesson = end($lesson);
        }

        return $lesson;
    }

    protected function getCourseMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    protected function getUploadFile()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getActivityService()
    {
        return $this->createService('CustomBundle:Activity:ActivityService');
    }

    protected function gerMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    protected function getCourseTaskResultService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }

    protected function getLessonEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }
}
