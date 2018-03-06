<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\Paginator;

class HomeworkManageController extends BaseController
{
    public function checkListAction(Request $request, $id, $status)
    {
        $course = $this->getCourseService()->getCourse($id);
        $course = $this->getCourseService()->tryManageCourse($course['id'], $course['courseSetId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $user = $this->getUser();
        $isTeacher = $this->getCourseMemberService()->isCourseTeacher($course['id'], $user['id']) || $user->isSuperAdmin();

        $reviewPracticeResultCount = $this->getReviewPracticeResultCount($course['id']);
        $reviewTopicResultCount = $this->getReviewTopicResultCount($course['id']);

        $templateDatas = array(
            'status' => $status,
            'courseSet' => $courseSet,
            'course' => $course,
            'isTeacher' => $isTeacher,
            'reviewTopicResultCount' => $reviewTopicResultCount,
            'reviewPracticeResultCount' => $reviewPracticeResultCount,
        );

        if ($status == 'homework') {
            $templateDatas = $this->getHomeworkTempDatas($templateDatas);
        } else {
            $templateDatas = $this->getPracticeWorkDatas($templateDatas);
        }
        return $this->render('course-manage/homework-check/check-list.html.twig', $templateDatas);
    }

    private function getHomeworkTempDatas($templateDatas)
    {
        $targetId = $templateDatas['course']['id'];
        $targetType = 'course';
        $type = 'homework';

        $activities = $this->getActivityService()->findActivitiesByCourseIdAndType($targetId, 'homework');
        $testpaperIds = ArrayToolkit::column($activities, 'mediaId');
        if (empty($testpaperIds)) {
            $testpaperIds = array(0);
        }

        $conditions = array(
            'status' => 'open',
            'type' => $type,
            'ids' => $testpaperIds,
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getTestpaperService()->searchTestpaperCount($conditions),
            20
        );

        $testpapers = $this->getTestpaperService()->searchTestpapersOrderByLessonNumAndTaskId(
            $testpaperIds,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $courseIds = array($targetId);
        if ($targetType === 'classroom') {
            $courses = $this->getClassroomService()->findCoursesByClassroomId($targetId);
            $courseIds = ArrayToolkit::column($courses, 'id');
        }

        foreach ($testpapers as $key => $testpaper) {
            $testpapers[$key]['resultStatusNum'] = $this->getTestpaperService()->findPaperResultsStatusNumGroupByStatus($testpaper['id'], $courseIds);
            $lastSubmit = $this->getTestpaperService()->getLastResultByTestId($testpaper['id']);
            if ($lastSubmit) {
                $testpapers[$key]['lastSubmit'] = $lastSubmit;
                $testpapers[$key]['lastSubmit']['user'] = $this->getUserService()->getUser($lastSubmit['userId']);
            }
        }

        $homeworkDatas = array(
            'testpapers' => ArrayToolkit::index($testpapers, 'id'),
            'paginator' => $paginator,
            'targetId' => $targetId,
            'targetType' => $targetType,
        );
        return array_merge($templateDatas, $homeworkDatas);
    }

    private function getPracticeWorkDatas($templateDatas)
    {
        $courseId = $templateDatas['course']['id'];

        $conditions = array(
            'fromCourseId' => $courseId,
            'mediaType' => 'practiceWork',
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getActivityService()->count($conditions),
            20
        );

        $practiceWorkActivitys = $this->getActivityService()->searchActivitysOrderByLessonNumAndTaskId(
            $conditions,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        foreach ($practiceWorkActivitys as $key => $practiceWorkActivity) {
            $practiceWorkActivitys[$key]['resultStatusNum'] = $this->getPracticeWorkService()->findResultsStatusNumGroupByStatus($practiceWorkActivitys[$key]['mediaId']);
            $practiceWorkActivitys[$key]['description'] = $practiceWorkActivitys[$key]['content'];
            $practiceWorkActivitys[$key]['name'] = $practiceWorkActivitys[$key]['title'];

            $lastSubmit = $this->getPracticeWorkService()->getLastResultByPracticeWorkId($practiceWorkActivity['mediaId']);
            if ($lastSubmit) {
                $practiceWorkActivitys[$key]['lastSubmit'] = $lastSubmit;
                $practiceWorkActivitys[$key]['lastSubmit']['user'] = $this->getUserService()->getUser($lastSubmit['userId']);
            }
        }

        $practiceWorkDatas = array(
            'testpapers' => ArrayToolkit::index($practiceWorkActivitys, 'id'),
            'paginator' => $paginator,
            'targetId' => $courseId,
            'targetType' => 'practice_work',
        );

        return array_merge($templateDatas, $practiceWorkDatas);
    }

    private function getReviewPracticeResultCount($courseId)
    {
        $activities = $this->getActivityService()->findActivitiesByCourseIdAndType($courseId, 'practiceWork');

        if (empty($activities)) {
            return 0;
        }
        return $this->getPracticeWorkService()->searchResultsCount(array(
            'practiceWorkIds' => ArrayToolkit::column($activities, 'mediaId'),
            'notFinishedStatus' => 'finished'
        ));
    }

    private function getReviewTopicResultCount($courseId)
    {
        $activities = $this->getActivityService()->findActivitiesByCourseIdAndType($courseId, 'homework');

        if (empty($activities)) {
            return 0;
        }
        return $this->getTestpaperService()->searchTestpaperResultsCount(array(
            'testIds' => ArrayToolkit::column($activities, 'mediaId'),
            'status' => 'reviewing'
        ));
    }

    protected function getPracticeWorkService()
    {
        return $this->createService('CustomBundle:Activity:PracticeWorkService');
    }

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

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    protected function getTestpaperService()
    {
        return $this->createService('CustomBundle:Testpaper:TestpaperService');
    }
}
