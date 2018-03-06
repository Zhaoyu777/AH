<?php

namespace CustomBundle\Controller\Question;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Question\ManageController as BaseManageController;

class ManageController extends BaseManageController
{
    public function indexAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);

        $sync = $request->query->get('sync');
        if ($courseSet['locked'] && empty($sync)) {
            return $this->redirectToRoute('course_set_manage_sync', array(
                'id' => $id,
                'sideNav' => 'question',
            ));
        }

        $conditions = $request->query->all();

        $conditions['courseSetId'] = $courseSet['id'];
        $conditions['parentId'] = empty($conditions['parentId']) ? 0 : $conditions['parentId'];

        $parentQuestion = array();
        $orderBy = array('createdTime' => 'DESC');
        if ($conditions['parentId'] > 0) {
            $parentQuestion = $this->getQuestionService()->get($conditions['parentId']);
            $orderBy = array('createdTime' => 'ASC');
        }

        $paginator = new Paginator(
            $this->get('request'),
            $this->getQuestionService()->searchCount($conditions),
            10
        );

        $questions = $this->getQuestionService()->search(
            $conditions,
            $orderBy,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($questions, 'updatedUserId'));

        $taskIds = ArrayToolkit::column($questions, 'lessonId');
        $courseTasks = $this->getTaskService()->findTasksByIds($taskIds);
        $courseTasks = ArrayToolkit::index($courseTasks, 'id');

        $courseIds = ArrayToolkit::column($questions, 'courseId');
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);

        $user = $this->getUser();
        $searchCourses = $this->getCourseService()->findUserManageCoursesByCourseSetId($user['id'], $courseSet['id']);

        $courseId = $request->query->get('courseId', 0);
        $showTasks = $this->getQuestionRanges($courseId);
        $courseLessons = array();

        if ($courseSet['type'] == 'instant') {
            $courseLessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

            $showTasks = $this->dealCourseTasks($courseId, $showTasks);
        }

        return $this->render('question-manage/index.html.twig', array(
            'courseSet' => $courseSet,
            'questions' => $questions,
            'users' => $users,
            'paginator' => $paginator,
            'parentQuestion' => $parentQuestion,
            'conditions' => $conditions,
            'courseTasks' => $courseTasks,
            'courses' => $courses,
            'searchCourses' => $searchCourses,
            'courseLessons' => $courseLessons ,
            'showTasks' => $showTasks,
        ));
    }

    public function questionPickerAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);

        $conditions = $request->query->all();

        $conditions['parentId'] = 0;
        $conditions['courseSetId'] = $courseSet['id'];

        $paginator = new Paginator(
            $request,
            $this->getQuestionService()->searchCount($conditions),
            7
        );

        $questions = $this->getQuestionService()->search(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $user = $this->getUser();
        $manageCourses = $this->getCourseService()->findUserManageCoursesByCourseSetId($user['id'], $courseSet['id']);

        $courseId = $request->query->get('courseId', 0);
        $courseTasks = $this->getQuestionRanges($courseId);
        $courseLessons = array();

        if ($courseSet['type'] == 'instant') {
            $courseLessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

            $courseTasks = $this->dealCourseTasks($courseId, $courseTasks);
        }

        return $this->render('question-manage/question-picker.html.twig', array(
            'courseSet' => $courseSet,
            'questions' => $questions,
            'replace' => empty($conditions['replace']) ? '' : $conditions['replace'],
            'paginator' => $paginator,
            'courseLessons' => $courseLessons,
            'courseTasks' => $courseTasks,
            'conditions' => $conditions,
            'targetType' => $request->query->get('targetType', 'testpaper'),
            'courses' => $manageCourses,
        ));
    }

    public function showTasksAction(Request $request, $courseSetId)
    {
        $courseId = $request->request->get('courseId', 0);
        if (empty($courseId)) {
            return $this->createJsonResponse(array());
        }

        $this->getCourseService()->tryManageCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        $courseTasks = $this->getQuestionRanges($courseId);
        $courseLessons = array();

        if ($courseSet['type'] == 'instant') {
            $courseLessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

            $courseTasks = $this->dealCourseTasks($courseId, $courseTasks);
        }

        return $this->createJsonResponse(array(
            'courseLessons' => $courseLessons,
            'courseTasks' => $courseTasks,
        ));
    }

    protected function getQuestionRanges($courseId)
    {
        $conditions = array(
            'courseId' => $courseId,
            'typesNotIn' => array('testpaper', 'homework'),
        );

        $courseTasks = $this->getTaskService()->searchTasks($conditions, array(), 0, PHP_INT_MAX);

        return ArrayToolkit::index($courseTasks, 'id');
    }

    protected function dealCourseTasks($courseId, $courseTasks)
    {
        $lessonTasks = $this->getCourseLessonService()->findLessonTasksByCourseId($courseId);
        $lessonTasks = ArrayToolkit::index($lessonTasks, 'taskId');

        $questionTypes = array('testpaper', 'randomTestpaper', 'homework');
        foreach ($courseTasks as $key => &$courseTask) {
            if (in_array($courseTask['type'], $questionTypes)) {
                unset($courseTasks[$key]);
            }
        }

        foreach ($courseTasks as $key => &$courseTask) {
            $courseTask['lessonId'] = $lessonTasks[$key]['lessonId'];
        }
        $courseTasks = ArrayToolkit::group($courseTasks, 'lessonId');

        $result = array();
        foreach ($courseTasks as $lessonId => $lessonTasks) {
            $chapters = $this->getCourseLessonService()->findCourseChaptersByLessonId($lessonId);
            $chapters = $this->chapterSort($chapters);
            $chapters = ArrayToolkit::index($chapters, 'id');

            $tasks = array();
            foreach ($lessonTasks as $lessonTask) {
                $tasks[$chapters[$lessonTask['categoryId']]['seq']] = $lessonTask;
            }
            $result[$lessonId] = $tasks;
        }

        return $result;
    }

    protected function chapterSort($chapters)
    {
        foreach ($chapters as &$chapter) {
            if ($chapter['stage'] == 'in') {
                $chapter['seq'] = $chapter['seq'] * 100;
            } elseif ($chapter['stage'] == 'after') {
                $chapter['seq'] = $chapter['seq'] * 10000;
            }
        }

        return $chapters;
    }

    public function showQuestionTypesNumAction(Request $request, $courseSetId)
    {
        $this->getCourseSetService()->tryManageCourseSet($courseSetId);

        $conditions = $request->request->all();

        if ($conditions['courseId'] == 'userId') {
            $user = $this->getCurrentUser();
            $conditions = array(
                'createdUserId' => $user['id'],
                'parentId' => 0,
            );
        } else {
            $conditions['courseSetId'] = $courseSetId;
            $conditions['parentId'] = 0;
        }

        $typesNum = $this->getQuestionService()->getQuestionCountGroupByTypes($conditions);
        $typesNum = ArrayToolkit::index($typesNum, 'type');

        return $this->createJsonResponse($typesNum);
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Question:QuestionService');
    }
}
