<?php

namespace CustomBundle\Controller;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\HomeworkManageController as BaseHomeworkManageController;

class HomeworkManageController extends BaseHomeworkManageController
{
    public function questionPickerAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);

        $conditions = $request->query->all();

        $conditions['courseSetId'] = $courseSet['id'];
        $conditions['parentId'] = 0;

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

            $lessonTasks = $this->getCourseLessonService()->findLessonTasksByCourseId($courseId);
            $lessonTasks = ArrayToolkit::index($lessonTasks, 'taskId');

            foreach ($courseTasks as $key => &$courseTask) {
                $courseTask['lessonId'] = $lessonTasks[$key]['lessonId'];
            }
            $courseTasks = ArrayToolkit::group($courseTasks, 'lessonId');
        }

        return $this->render('homework/manage/question-picker.html.twig', array(
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

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
