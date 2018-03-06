<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Common\PushMsgToolkit;
use Biz\Task\Strategy\StrategyContext;
use Symfony\Component\HttpFoundation\Request;

class CourseLessonController extends BaseController
{
    public function showEndButtonAction($lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        return $this->render('custom-task/end-class-modal.html.twig', array(
            'lesson' => $lesson
        ));
    }

    public function indexAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);

        if ($course['status'] == 'delete') {
            return $this->createMessageResponse('info', '该课程已删除', null, 3000, $this->generateUrl('my_teaching_instant_courses'));
        }
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);
        $lessonIds = ArrayToolkit::column($lessons, 'id');
        $taskCounts = $this->getCourseLessonService()->countLessonTasksByLessonIds($lessonIds);
        $lessons = $this->sortLessons($lessons);

        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->render('prepare-course-manage/lesson-manage/lessons.html.twig', array(
            'lessons' => $lessons,
            'course' =>  $course,
            'taskCounts' => $taskCounts,
            'courseSet' => $courseSet,
        ));
    }

    private function sortLessons($lessons)
    {
        $finishedLessons = array();
        $unFinishedLessons = array();
        foreach ($lessons as $lesson) {
            if ($lesson['status'] == 'teached') {
                if (!empty($unFinishedLessons)) {
                    $finishedLessons = array_merge($finishedLessons, $unFinishedLessons);
                    $unFinishedLessons = array();
                }
                $finishedLessons[] = $lesson;
            } elseif ($lesson['status'] == 'teaching') {
                if (!empty($unFinishedLessons)) {
                    $finishedLessons = array_merge($finishedLessons, $unFinishedLessons);
                    $unFinishedLessons = array();
                }
                $unFinishedLessons[] = $lesson;
            } else {
                $unFinishedLessons[] = $lesson;
            }
        }

        return array_merge($unFinishedLessons, $finishedLessons);
    }

    public function tasksAction(Request $request, $lessonId)
    {
        $lesson   = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course = $this->getCourseService()->tryManageCourse($lesson['courseId']);
        if ($course['status'] == 'delete') {
            return $this->createMessageResponse('info', '该课程已删除', null, 3000, $this->generateUrl('my_teaching_instant_courses'));
        }

        $chapters = $this->getCourseLessonService()->findChaptersByLessonId($lessonId);
        $chapters = ArrayToolkit::index($chapters, 'categoryId');
        $lessonItems = $this->getCourseLessonService()->findCourseLessonItems($lessonId);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $teachingAims = $this->getTeachingAimService()->findByLessonId($lessonId);

        return $this->render('prepare-course-manage/lesson-manage/tasks.html.twig', array(
            'lesson'   => $lesson,
            'chapters' => $chapters,
            'course' => $course,
            'items' => $lessonItems,
            'courseSet' => $courseSet,
            'teachingAims' => $this->getTeachingAimService()->processAims($teachingAims),
        ));
    }


    public function editAction(Request $request, $lessonId)
    {
        $lesson    = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course    = $this->getCourseService()->tryManageCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $courseLessonCount = $this->getCourseLessonService()->countCourseLessonByCourseId(array('courseId' => $lesson['courseId']));
        $lessonAims = $this->getTeachingAimService()->findByLessonId($lessonId);

        if ($request->getMethod() === 'POST') {
            $fields = $request->request->all();

            $fields = $this->processEditFields($fields);

            $this->getCourseLessonService()->updateCourseLesson($lessonId, $fields);

            return $this->createJsonResponse(true);
        }

        return $this->render('prepare-course-manage/lesson-manage/lesson-edit-modal.html.twig', array(
            'lesson'    => $lesson,
            'courseSet' => $courseSet,
            'course'    => $course,
            'courseLessonCount' => $courseLessonCount,
            'lessonAims' => $this->processAimsWithJson($lessonAims),
        ));
    }

    protected function processEditFields($fields)
    {
        if (empty($fields['abilityAim'])) {
            return $fields;
        }
        $fields['abilityAims']['modifyAims'] = json_decode($fields['abilityAim'], true);
        $fields['abilityAims']['deleteAims'] = json_decode($fields['deleteabilityAim'], true);
        unset($fields['abilityAim']);
        unset($fields['deleteabilityAim']);

        $fields['knowledgeAims']['modifyAims'] = json_decode($fields['knowledgeAim'], true);
        $fields['knowledgeAims']['deleteAims'] = json_decode($fields['deleteknowledgeAim'], true);
        unset($fields['knowledgeAim']);
        unset($fields['deleteknowledgeAim']);

        $fields['qualityAims']['modifyAims'] = json_decode($fields['qualityAim'], true);
        $fields['qualityAims']['deleteAims'] = json_decode($fields['deletequalityAim'], true);
        unset($fields['qualityAim']);
        unset($fields['deletequalityAim']);

        return $fields;
    }

    protected function processAimsWithJson($lessonAims)
    {
        $typeAims = ArrayToolkit::group($lessonAims, 'type');

        $datas = array();
        foreach ($typeAims as $type => $aims) {
            foreach ($aims as $aim) {
                $datas[$type][] = array(
                    'id' => $aim['id'],
                    'content' => $aim['content'],
                );
            }

            $datas[$type] = json_encode($datas[$type]);
        }

        return $datas;
    }

    public function editHintAction(Request $request)
    {
        $status = $request->query->get('status');

        return $this->render('prepare-course-manage/lesson-manage/lesson-status-modal.html.twig', array(
            'status' => $status,
        ));
    }

    public function createChapterAction(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $fields = $request->request->all();

            $chapter = $this->getCourseLessonService()->createChapter($fields);

            $fields = $this->generateDefaultCourseChapter($chapter);
            $courseChapter = $this->getCourseService()->createChapter($fields);
            $this->getCourseLessonService()->updateChapter($chapter['id'], array('categoryId' => $courseChapter['id']));

            return $this->createJsonResponse(true);
        }

        return $this->render('prepare-course-manage/lesson-manage/lesson-chapter-modal.html.twig', array(
            'chapter' => $request->query->all()
        ));
    }

    private function generateDefaultCourseChapter($chapter)
    {
        return array(
            'courseId' => $chapter['courseId'],
            'lessonId' => $chapter['lessonId'],
            'seq' => $this->getCourseService()->getNextCourseLessonItemSeq($chapter['courseId'], $chapter['lessonId']) + 40,
            'type' => 'chapter',
            'title' => $chapter['title'],
        );
    }

    public function editChapterAction(Request $request, $chapterId)
    {
        if ($request->getMethod() === 'POST') {
            $fields = $request->request->all();

            $this->getCourseLessonService()->updateChapter($chapterId, $fields);

            return $this->createJsonResponse(true);
        }

        $chapter = $this->getCourseLessonService()->getChapter($chapterId);

        return $this->render('prepare-course-manage/lesson-manage/lesson-chapter-modal.html.twig', array(
            'chapter' => $chapter
        ));
    }

    public function deleteChapterAction($chapterId)
    {
        $this->getCourseLessonService()->deleteChapter($chapterId);

        return $this->createJsonResponse(true);
    }

    public function loadLessonActivitiesAction(Request $request, $lessonId, $prepare = false, $taskId = null)
    {
        $contract = $request->query->get('contract');
        $lesson   = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $chapters = $this->getCourseLessonService()->findChaptersByLessonId($lessonId);
        $lessonItems = $this->getCourseLessonService()->findCourseLessonItems($lessonId);
        $chapters = ArrayToolkit::index($chapters, 'categoryId');
        $course = $this->getCourseService()->getCourse($lesson['courseId']);

        if ($prepare) {
            return $this->render('course-manage/custom-lesson/lesson-prepare-activity-item.html.twig', array(
                'contract' => $contract,
                'items' => $lessonItems,
                'chapters' => $chapters,
                'course' => $course,
                'lesson' => $lesson,
            ));
        }

        return $this->render('course-manage/custom-lesson/lesson-activity-item.html.twig', array(
            'contract' => $contract,
            'items' => $lessonItems,
            'chapters' => $chapters,
            'course' => $course,
            'lesson' => $lesson,
            'taskId' => $taskId,
        ));
    }

    public function startLessonAction($courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryStartCourse($courseId);

        if ($this->getCourseService()->isAnyLessonStart($courseId)) {
            return $this->createJsonResponse(array(
                'status' => 'failed',
                'message' => '已经有课次正在上课中'
            ));
        }

        $this->getCourseLessonService()->startCourseLesson($lessonId);

        return $this->createJsonResponse(array(
            'status' => 'success',
            'message' => ''
        ));
    }

    public function loadLessonsAction(Request $request, $courseId)
    {
        $lessonId = $request->query->get('lessonId');
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

        return $this->render('course-manage/custom-lesson/lessons.html.twig', array(
            'lessons' => $lessons,
            'lessonId' => $lessonId,
        ));
    }

    public function teachLessonsAction(Request $request, $courseId)
    {
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);
        $source = $request->query->get('source', '');

        return $this->render('teach-lesson/lessons-modal.html.twig', array(
            'lessons' => $lessons,
            'courseId' => $courseId,
            'source' => $source,
        ));
    }

    public function prepareImportCourseLessonsAction($courseId)
    {
        $lessons =$this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

        $isFilter = false;
        $result = array();
        foreach ($lessons as $key => $lesson) {
            $taskCount = $this->getCourseLessonService()->countLessonTask(array('lessonId' => $lesson['id']));
            if (!$taskCount) {
                $isFilter = true;
                continue ;
            }
            $result[] = array(
                'id' => $lesson['id'],
                'count' => "课次{$lesson['number']} {$lesson['title']}"
            );
        }

        return $this->createJsonResponse(array(
            'isFilter' => $isFilter,
            'lessons' => $result,
        ));
    }

    public function weixinPreviewAction(Request $request, $courseId, $lessonId)
    {
        $host = $request->getHttpHost();
        $src = "http://{$host}/weixin/index.html#/course/{$courseId}/study?preview=1&lessonId=".$lessonId;

        return $this->render('course-manage/custom-course/course-review-modal.html.twig', array(
            'src' => $src,
        ));
    }

    public function getPushParamsAction($courseId, $lessonId)
    {
        $user = $this->getCurrentUser();
        $courseMember = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        $pushTool = $this->getPushTool();
        $data = array(
            'room' => "course-{$courseId}-lesson-{$lessonId}",
            'id' => $user['id'],
            'role' => $courseMember['role']
        );

        $token = $pushTool->getPushToken($data);
        $config = $this->getParameter('push_server');

        return $this->createJsonResponse(array(
            'token' => $token,
            'serverUrl' => "{$config['protocol']}://{$config['host']}:{$config['port']}",
        ));
    }

    protected function getPushTool()
    {
        return PushMsgToolkit::getInstance();
    }

    protected function getTeachingAimService()
    {
        return $this->createService('CustomBundle:Lesson:TeachingAimService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
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

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }
}
