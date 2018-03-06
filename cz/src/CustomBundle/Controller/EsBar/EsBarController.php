<?php

namespace CustomBundle\Controller\EsBar;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\EsBar\EsBarController as BaseEsBarController;
use Symfony\Component\HttpFoundation\Request;

class EsBarController extends BaseEsBarController
{
    public function courseAction(Request $request)
    {
        $user = $this->getUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('用户没有登录,不能查看!');
        }

        $conditions = array(
            'userId' => $user->id,
            'locked' => 0,
            'classroomId' => 0,
            'role' => 'student',
        );
        $sort = array('createdTime' => 'DESC');
        $members = $this->getCourseMemberService()->searchMembers($conditions, $sort, 0, 15);
        $courseIds = ArrayToolkit::column($members, 'courseId');
        $courseConditions = array(
            'courseIds' => $courseIds,
            'parentId' => 0,
            'excludeType' => 'instant',
        );
        $courses = $this->getCourseService()->searchCourses($courseConditions, 'default', 0, 15);
        $courses = ArrayToolkit::index($courses, 'id');
        $sortedCourses = array();

        if (!empty($courses)) {
            foreach ($members as $member) {
                if (empty($courses[$member['courseId']])) {
                    continue;
                }

                $course = $courses[$member['courseId']];

                if ($course['taskNum'] != 0) {
                    $course['percent'] = intval($member['learnedNum'] / $course['taskNum'] * 100);
                } else {
                    $course['percent'] = 0;
                }

                $sortedCourses[] = $course;
            }
        }

        return $this->render(
            'es-bar/list-content/study-place/my-course.html.twig',
            array(
                'courses' => $sortedCourses,
            )
        );
    }
}