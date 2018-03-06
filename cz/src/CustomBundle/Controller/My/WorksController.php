<?php

namespace CustomBundle\Controller\My;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

Class WorksController extends BaseController
{
    public function listAction(Request $request, $status)
    {
        list($courses,$courseSets) = $this->getCoursesAndCourseSets();
        $works = array();
        $templateDatas = array(
            'status' => $status,
            'courses' => $courses,
            'courseSets' => $courseSets,
        );
        if ($status == 'homework') {
            $templateDatas = $this->getHomeWorkTemplateDatas($templateDatas);
        } else {
            $templateDatas = $this->getPracticeWorkTemplateDatas($templateDatas);
        }

        return $this->render('my/works/list.html.twig', $templateDatas);
    }

    public function otherListAction(Request $request, $status)
    {
        $user = $this->getUser();

        $conditions = array(
            'status' => $status,
            'type' => 'homework',
            'userId' => $user['id'],
        );

        $paginator = new Paginator(
            $request,
            $this->getTestpaperService()->countOnlineTestpaperResults($conditions),
            10
        );

        $paperResults = $this->getTestpaperService()->searchOnlineTestpaperResults(
            $conditions,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $courseIds = ArrayToolkit::column($paperResults, 'courseId');
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);

        $courseSetIds = ArrayToolkit::column($paperResults, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        $activityIds = ArrayToolkit::column($paperResults, 'lessonId');
        $tasks = $this->getTaskService()->findTasksByActivityIds($activityIds);

        $homeworkIds = ArrayToolkit::column($paperResults, 'testId');
        $homeworks = $this->getTestpaperService()->findTestpapersByIds($homeworkIds);

        return $this->render('my/homework/my-homework-list.html.twig', array(
            'paperResults' => $paperResults,
            'paginator' => $paginator,
            'courses' => $courses,
            'courseSets' => $courseSets,
            'status' => $status,
            'homeworks' => $homeworks,
            'tasks' => $tasks,
        ));
    }

    private function getCoursesAndCourseSets()
    {
        $user = $this->getCurrentUser();
        $courses = $this->getCourseService()->findInstantCoursesByUserId($user['id']);
        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);
        return array($courses, $courseSets);
    }

    private function getPracticeWorkTemplateDatas($templateDatas)
    {
        $user = $this->getCurrentUser();
        $count = $this->getPracticeWorkService()->searchResultsCount(array('userId' => $user['id']));
        $paginator = new Paginator(
            $this->get('request'),
            $count,
            20
        );
        $results = $this->getPracticeWorkService()->searchResults(
            array('userId' => $user['id']),
            array('updatedTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $activities = $this->getActivityService()->findActivities(ArrayToolkit::column($results, 'activityId'));

        return array_merge($templateDatas, array('results' => $results, 'paginator' => $paginator, 'activities' => ArrayToolkit::index($activities, 'id')));
    }

    private function getHomeWorkTemplateDatas($templateDatas)
    {
        $user = $this->getCurrentUser();
        $courseIds = ArrayToolkit::column($templateDatas['courses'], 'id');
        $conditions = array('type' => 'homework', 'courseIds' => $courseIds, 'userId' => $user['id']);
        $resultCount = $this->getTestpaperService()->searchTestpaperResultsCount($conditions);
        $paginator = new Paginator(
            $this->get('request'),
            $resultCount,
            20
        );
        $results = $this->getTestpaperService()->searchTestpaperResults(
            $conditions,
            array('updateTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $testpapers = $this->getTestpaperService()->findTestpapersByIds(ArrayToolkit::column($results, 'testId'));
        return array_merge($templateDatas, array('paginator' => $paginator, 'results' => $results, 'testpapers' => $testpapers));
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getTestpaperService()
    {
        return $this->createService('CustomBundle:Testpaper:TestpaperService');
    }

    protected function getPracticeWorkService()
    {
        return $this->createService('CustomBundle:Activity:PracticeWorkService');
    }

    protected function getActivityService()
    {
        return $this->createService('CustomBundle:Activity:ActivityService');
    }

    protected function getTaskService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }
}
