<?php

namespace CustomBundle\Controller\Testpaper;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\Testpaper\ManageController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\Paginator;

class ManageController extends BaseController
{
    public function createAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);

        $user = $this->getUser();
        if ($request->getMethod() === 'POST') {
            $fields = $request->request->all();
            if ($fields['ranges']['courseId'] == 'userId') {
                $fields['ranges'] = array(
                    'createdUserId' => $user['id'],
                    'parentId' => 0,
                );
            } else {
                $fields['courseId'] = 0;
            }
            $fields['courseSetId'] = $courseSet['id'];

            $fields['pattern'] = 'questionType';

            $testpaper = $this->getTestpaperService()->buildTestpaper($fields, 'testpaper');

            return $this->redirect(
                $this->generateUrl(
                    'course_set_manage_testpaper_questions',
                    array('courseSetId' => $courseSet['id'], 'testpaperId' => $testpaper['id'])
                )
            );
        }

        $types = $this->getQuestionTypes();

        // $conditions = array(
        //     'types' => array_keys($types),
        //     'courseSetId' => $courseSet['id'],
        //     'parentId' => 0,
        // );
        $conditions = array(
            'types' => array_keys($types),
            'createdUserId' => $user['id'],
            'parentId' => 0,
        );

        $questionNums = $this->getQuestionService()->getQuestionCountGroupByTypes($conditions);
        $questionNums = ArrayToolkit::index($questionNums, 'type');

        $ranges = $this->getTaskService()->findUserTeachCoursesTasksByCourseSetId($user['id'], $courseSet['id']);

        $manageCourses = $this->getCourseService()->findUserManageCoursesByCourseSetId($user['id'], $courseSet['id']);

        return $this->render('testpaper/manage/create.html.twig', array(
            'courseSet' => $courseSet,
            'ranges' => $ranges,
            'types' => $types,
            'questionNums' => $questionNums,
            'courses' => $manageCourses,
        ));
    }

    public function buildCheckAction(Request $request, $courseSetId, $type)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($courseSetId);

        $data = $request->request->all();

        $data['courseSetId'] = $courseSet['id'];

        if ($data['ranges']['courseId'] == 'userId') {
            $user = $this->getCurrentUser();
            $data['ranges'] = array(
                'createdUserId' => $user['id'],
                'parentId' => 0,
            );
        }

        $result = $this->getTestpaperService()->canBuildTestpaper($type, $data);

        return $this->createJsonResponse($result);
    }

    public function practiceResultListAction(Request $request, $id, $testpaperId)
    {
        $course = $this->getCourseService()->getCourse($id);
        $course = $this->getCourseService()->tryManageCourse($course['id'], $course['courseSetId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $user = $this->getUser();
        $activity = $this->getActivityService()->getActivity($testpaperId);

        if (!$activity) {
            throw $this->createResourceNotFoundException('activity', $activity['id']);
        }

        $isTeacher = $this->getCourseMemberService()->isCourseTeacher($course['id'], $user['id']) || $user->isSuperAdmin();

        return $this->render('course-manage/homework-check/result.html.twig', array(
            'course' => $course,
            'courseSet' => $courseSet,
            'activity' => $activity,
            'isTeacher' => $isTeacher,
        ));
    }

    public function resultListShowAction(Request $request, $activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);

        if (!$activity) {
            throw $this->createResourceNotFoundException('activity', $activity['id']);
        }

        $status = $request->query->get('status', 'finished');
        $keyword = $request->query->get('keyword', '');

        if (!in_array($status, array('all', 'finished', 'reviewing'))) {
            $status = 'all';
        }

        $conditions = array('practiceWorkId' => $activity['mediaId']);
        if ($status == 'finished') {
            $conditions['status'] = $status;
        }
        if ($status == 'reviewing') {
            $conditions['notFinishedStatus'] = 'finished';
        }

        if (!empty($keyword)) {
            $searchUser = $this->getUserService()->getUserByNickname($keyword);
            $conditions['userId'] = $searchUser ? $searchUser['id'] : '-1';
        }

        $paginator = new Paginator(
            $request,
            $this->getPracticeWorkService()->searchResultsCount($conditions),
            10
        );

        $practiceWorkResults = $this->getPracticeWorkService()->searchResults(
            $conditions,
            array('updatedTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $studentIds = ArrayToolkit::column($practiceWorkResults, 'userId');
        $teacherIds = ArrayToolkit::column($practiceWorkResults, 'checkTeacherId');
        $userIds = array_merge($studentIds, $teacherIds);
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('course-manage/homework-check/result-list.html.twig', array(
            'activity' => $activity,
            'status' => $status,
            'practiceWorkResults' => $practiceWorkResults,
            'paginator' => $paginator,
            'users' => $users,
            'isTeacher' => true,
            'keyword' => $keyword,
        ));
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Question:QuestionService');
    }

    protected function getPracticeWorkService()
    {
        return $this->createService('CustomBundle:Activity:PracticeWorkService');
    }

    protected function getActivityService()
    {
        return $this->createService('CustomBundle:Activity:ActivityService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
