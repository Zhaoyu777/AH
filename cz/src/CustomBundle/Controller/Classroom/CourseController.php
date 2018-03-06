<?php

namespace CustomBundle\Controller\Classroom;

use AppBundle\Controller\Classroom\CourseController as BaseController;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\ClassroomToolkit;
use AppBundle\Common\Paginator;
use Symfony\Component\HttpFoundation\Request;

class CourseController extends BaseController
{
    public function pickAction($classroomId)
    {
        $this->getClassroomService()->tryManageClassroom($classroomId);

        $conditions = array(
            'status' => 'published',
            'excludeType' => 'instant',
            'parentId' => 0,
        );

        $activeCourses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroomId);
        if (!empty($activeCourses)) {
            $conditions['excludeIds'] = ArrayToolkit::column($activeCourses, 'parentCourseSetId');
        }

        $user = $this->getCurrentUser();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            $conditions['creator'] = $user['id'];
        }
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countCourseSets($conditions),
            5
        );

        $courseSets = $this->searchCourseSetWithCourses(
            $conditions,
            array('updatedTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUsers($courseSets);

        return $this->render(
            'classroom-manage/course/course-pick-modal.html.twig',
            array(
                'users' => $users,
                'courseSets' => $courseSets,
                'classroomId' => $classroomId,
                'paginator' => $paginator,
            )
        );
    }

    public function searchAction(Request $request, $classroomId)
    {
        $this->getClassroomService()->tryManageClassroom($classroomId);
        $key = $request->request->get('key');

        $activeCourses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroomId);
        $excludeIds = ArrayToolkit::column($activeCourses, 'parentCourseSetId');

        $conditions = array('title' => "%{$key}%");
        $conditions['status'] = 'published';
        $conditions['excludeType'] = 'instant';
        $conditions['parentId'] = 0;
        $conditions['excludeIds'] = $excludeIds;

        $user = $this->getCurrentUser();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            $conditions['creator'] = $user['id'];
        }

        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countCourseSets($conditions),
            5
        );

        $courseSets = $this->searchCourseSetWithCourses(
            $conditions,
            array('updatedTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUsers($courseSets);

        return $this->render(
            'course/course-select-list.html.twig',
            array(
                'users' => $users,
                'courseSets' => $courseSets,
                'paginator' => $paginator,
                'classroomId' => $classroomId,
                'type' => 'ajax_pagination',
            )
        );
    }

    private function searchCourseSetWithCourses($conditions, $orderbys, $start, $limit)
    {
        $courseSets = $this->getCourseSetService()->searchCourseSets($conditions, $orderbys, $start, $limit);

        if (empty($courseSets)) {
            return array();
        }

        $courseSets = ArrayToolkit::index($courseSets, 'id');
        $courses = $this->getCourseService()->findCoursesByCourseSetIds(array_keys($courseSets));
        if (!empty($courses)) {
            foreach ($courses as $course) {
                if ($course['status'] != 'published') {
                    continue;
                }

                if (empty($courseSets[$course['courseSetId']]['courses'])) {
                    $courseSets[$course['courseSetId']]['courses'] = array($course);
                } else {
                    $courseSets[$course['courseSetId']]['courses'][] = $course;
                }
            }
        }

        return array_values($courseSets);
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }
}
