<?php

namespace CustomBundle\Controller\Activity;

use Biz\Course\Service\CourseService;
use AppBundle\Controller\BaseController;
use Biz\Activity\Service\ActivityService;
use Biz\Testpaper\Service\TestpaperService;
use Symfony\Component\HttpFoundation\Request;
use Biz\Activity\Service\TestpaperActivityService;

class TestpaperController extends BaseController
{
    public function showAction(Request $request, $activity, $task, $preview = 0)
    {
        if ($preview) {
            return $this->previewTestpaper($activity['id'], $activity['fromCourseId'], $task);
        }

        $user = $this->getUser();
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperActivity['mediaId'], $activity['mediaType']);

        $course = $this->getCourseService()->getCourse($activity['fromCourseId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if (!$testpaper || $testpaper['status'] != 'open') {
            return $this->render('testpaper/fail.html.twig', array(
                'questionnaire' => null,
                'task' => $task,
                'course' => $course,
                'lesson' => $lesson,
            ));
        }

        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        if ($courseSet['type'] == 'instant') {
            if ($this->getCourseMemberService()->isCourseTeacher($task['courseId'], $user['id'])) {
                return $this->redirect($this->generateUrl('testpaper_statis', array(
                    'activityId' => $activity['id'],
                    'testId' => $testpaperActivity['mediaId'],
                    'course' => $course,
                    'task' => $task,
                    'isDone' => true,
                )));
            }
        }

        $testpaperResult = $this->getTestpaperService()->getUserLatelyResultByTestId($user['id'], $testpaperActivity['mediaId'], $activity['fromCourseId'], $activity['id'], $activity['mediaType']);

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if ($lesson['status'] == 'teached') {
            return $this->forward('CustomBundle:Testpaper/Testpaper:doTestpaper', array(
                'testId' => $testpaperActivity['mediaId'],
                'lessonId' => $activity['id'],
                'course' => $course,
                'task' => $task,
                'isDone' => true,
            ));
        }
        if (!$testpaperResult || ($testpaperResult['status'] == 'doing' && !$testpaperResult['updateTime']) || $testpaper['status'] != 'open') {
            return $this->render('activity/testpaper/show.html.twig', array(
                'activity' => $activity,
                'testpaperActivity' => $testpaperActivity,
                'testpaperResult' => $testpaperResult,
                'testpaper' => $testpaper,
                'lesson' => $lesson,
                'task' => $task,
                'course' => $course,
                'courseId' => $activity['fromCourseId'],
                'type' => $courseSet['type'],
                'isDone' => true,
            ));
        } elseif ($testpaperResult['status'] === 'finished') {
            return $this->forward('AppBundle:Testpaper/Testpaper:showResult', array(
                'resultId' => $testpaperResult['id'],
            ));
        }

        return $this->forward('AppBundle:Testpaper/Testpaper:doTestpaper', array(
            'testId' => $testpaperActivity['mediaId'],
            'lessonId' => $activity['id'],
        ));
    }

    public function previewAction(Request $request, $task)
    {
        return $this->previewTestpaper($task['activityId'], $task['courseId'], $task);
    }

    public function previewTestpaper($id, $courseId, $task)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperActivity['mediaId'], $activity['mediaType']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        if (!$testpaper || $testpaper['status'] != 'open') {
            return $this->render('activity/testpaper/preview.html.twig', array(
                'paper' => null,
                'task' => $task,
                'course' => $course,
                'isDone' => true,
            ));
        }

        $questions = $this->getTestpaperService()->showTestpaperItems($testpaper['id']);

        $total = $this->getTestpaperService()->countQuestionTypes($testpaper, $questions);

        $attachments = $this->getTestpaperService()->findAttachments($testpaper['id']);


        return $this->render('activity/testpaper/preview.html.twig', array(
            'questions' => $questions,
            'limitedTime' => $testpaperActivity['limitedTime'],
            'paper' => $testpaper,
            'paperResult' => array(),
            'total' => $total,
            'course' => $course,
            'task' => $task,
            'activity' => $activity,
            'attachments' => $attachments,
            'questionTypes' => $this->getCheckedQuestionType($testpaper),
            'isDone' => true,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $activity = $this->getActivityService()->getActivity($id);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        if ($testpaperActivity) {
            $testpaperActivity['testpaperMediaId'] = $testpaperActivity['mediaId'];
            unset($testpaperActivity['mediaId']);
        }
        $activity = array_merge($activity, $testpaperActivity);

        $testpapers = $this->findCourseTestpapers($course);

        $features = $this->container->hasParameter('enabled_features') ? $this->container->getParameter('enabled_features') : array();

        $testpaperCount = $this->getCourseTestpaperCount($course);

        return $this->render('activity/testpaper/modal.html.twig', array(
            'activity' => $activity,
            'testpapers' => $testpapers,
            'features' => $features,
            'courseId' => $activity['fromCourseId'],
            'course' => $course,
            'courseSet' => $courseSet,
            'testpaperCount' => $testpaperCount
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $testpapers = $this->findCourseTestpapers($course);
        $testpaperCount = $this->getCourseTestpaperCount($course);

        $features = $this->container->hasParameter('enabled_features') ? $this->container->getParameter('enabled_features') : array();

        return $this->render('activity/testpaper/modal.html.twig', array(
            'courseId' => $courseId,
            'testpapers' => $testpapers,
            'features' => $features,
            'course' => $course,
            'courseSet' => $courseSet,
            'testpaperCount' => $testpaperCount
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        return $this->render('activity/testpaper/finish-condition.html.twig', array(
            'testpaperActivity' => $testpaperActivity,
        ));
    }

    protected function findCourseTestpapers($course)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $conditions = array(
            'courseSetId' => $course['courseSetId'],
            'status' => 'open',
            'type' => 'testpaper',
        );

        if ($courseSet['parentId'] > 0 && $courseSet['locked']) {
            $conditions['copyIdGT'] = 0;
        }

        $testpapers = $this->getTestpaperService()->searchTestpapers(
            $conditions,
            array('createdTime' => 'DESC'),
            0,
            PHP_INT_MAX
        );

        return $testpapers;
    }

    protected function getCourseTestpaperCount($course)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $conditions = array(
            'courseSetId' => $course['courseSetId'],
            'type' => 'testpaper',
        );

        return $this->getTestpaperService()->searchTestpaperCount($conditions);
    }

    protected function getCheckedQuestionType($testpaper)
    {
        $questionTypes = array();
        if (!empty($testpaper['metas']['counts'])) {
            foreach ($testpaper['metas']['counts'] as $type => $count) {
                if ($count > 0) {
                    $questionTypes[] = $type;
                }
            }
        }

        return $questionTypes;
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @return TestpaperActivityService
     */
    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }
}
