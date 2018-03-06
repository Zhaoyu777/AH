<?php

namespace CustomBundle\Controller\My;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class CourseSetController extends BaseController
{
    public function teachingInstantCoursesAction(Request $request)
    {
        $user = $this->getCurrentUser();

        if (!$user->isTeacher()) {
            return $this->createMessageResponse('error', '您不是老师，不能查看此页面！');
        }
        $courseType = $request->query->get('courseType', 'school');
        $customCourseCount = 0;

        if ($courseType == 'custom') {
            $customCourseCount = $this->getCourseMemberService()->countTeachingCustomMembers($user['id']);
        }

        return $this->render('my/teaching/instant-courses.html.twig', array(
            'courseType' => $courseType,
            'customCourseCount' => $customCourseCount,
        ));
    }


    public function loadTeachingInstantCoursesAction(Request $request)
    {
        $user = $this->getCurrentUser();

        if (!$user->isTeacher()) {
            return $this->createMessageResponse('error', '您不是老师，不能查看此页面！');
        }

        $termCode = $request->request->get('termCode');
        $courseType = $request->request->get('courseType', 'school');

        return $this->render(
            "my/teaching/instant-courses-{$courseType}-tr.html.twig",
            $this->getTeachingInstantCourses($request, $user['id'], $termCode, $courseType)
        );
    }

    private function getTeachingInstantCourses($request, $userId, $termCode, $courseType)
    {
        $members = $this->getCourseMemberService()->findCurrentTermTeacherMembersByUserId($userId, $termCode);
        if (empty($members)) {
            $paginator = new Paginator(
                $request,
                0,
                20
            );

            return array(
                'paginator' => $paginator,
                'courses' => array(),
                'courseSets' => array(),
                'termCode' => $termCode,
                'courseType' => $courseType,
            );
        }

        $courseSetIds = ArrayToolkit::column($members, 'courseSetId');
        $courseSetIds = array_unique($courseSetIds);

        $conditions = array(
            'ids' => $courseSetIds,
            'type' => 'instant',
            'status' => 'published',
            'termCode' => $termCode,
            'courseType' => $courseType,
        );

        $perCount = 10;
        $orderBy = 'ASC';

        if ($courseType == 'custom') {
            $perCount = $perCount;
            $orderBy = 'DESC';
        }

        $paginator = new Paginator(
            $request,
            $this->getCourseSetService()->countCourseSetsWithCourseNo($conditions),
            $perCount
        );

        $courseSets = $this->getCourseSetService()->searchCourseSetsWithCourseNo(
            $conditions,
            array('createdTime' => $orderBy),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $members = ArrayToolkit::group($members, 'courseSetId');
        $courseIds = array();
        foreach ($courseSets as $courseSet) {
            $tempCourseIds = ArrayToolkit::column($members[$courseSet['id']], 'courseId');
            $courseIds = array_merge($courseIds, $tempCourseIds);
        }

        $courses = $this->getCourseService()->findCoursesByIds($courseIds);
        if ($courseType == 'custom') {
            $courses = ArrayToolkit::index($courses, 'courseSetId');
        } else {
            $courses = ArrayToolkit::group($courses, 'courseSetId');
        }

        return array(
            'paginator' => $paginator,
            'courses' => $courses,
            'courseSets' => $courseSets,
            'termCode' => $termCode,
            'courseType' => $courseType,
        );
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
}
