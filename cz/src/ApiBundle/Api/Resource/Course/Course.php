<?php

namespace ApiBundle\Api\Resource\Course;

use ApiBundle\Api\Annotation\ApiConf;
use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Exception\ErrorCode;
use ApiBundle\Api\Resource\AbstractResource;
use Biz\Classroom\Service\ClassroomService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Course extends AbstractResource
{
    /**
     * @ApiConf(isRequiredAuth=false)
     */
    public function get(ApiRequest $request, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);

        if (!$course) {
            throw new NotFoundHttpException('教学计划不存在', null, ErrorCode::RESOURCE_NOT_FOUND);
        }

        $user = $this->getCurrentUser();

        if ($user->isLogin()) {
            $member = $this->getMemberService()->getCourseMember($course['id'], $user['id']);
        }

        if ($course['parentId'] > 0) {
            $classroom = $this->getClassroomService()->getClassroomByCourseId($course['id']);
        }

        if (!empty($classroom) && empty($member)) {
            $this->joinCourseMemberByClassroomId($course['id'], $classroom['id']);
        }

        $this->getOCUtil()->single($course, array('creator', 'teacherIds'));
        $this->getOCUtil()->single($course, array('courseSetId'), 'courseSet');

        $course['access'] = $this->getCourseService()->canJoinCourse($courseId);

        return $course;
    }


    protected function joinCourseMemberByClassroomId($courseId, $classroomId)
    {
        $classroom = $this->getClassroomService()->getClassroom($classroomId);
        $user = $this->getCurrentUser();

        $classroomMember = $this->getClassroomService()->getClassroomMember($classroom['id'], $user['id']);

        if (empty($classroomMember) || !in_array('student', $classroomMember['role'])) {
            return;
        }

        $info = array(
            'levelId' => empty($classroomMember['levelId']) ? 0 : $classroomMember['levelId'],
            'deadline' => $classroomMember['deadline'],
        );

        $this->getMemberService()->createMemberByClassroomJoined($courseId, $user['id'], $classroom['id'], $info);
    }

    /**
     * @ApiConf(isRequiredAuth=false)
     */
    public function search(ApiRequest $request)
    {
        $conditions = $request->query->all();
        $conditions['status'] = 'published';

        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $courses = $this->service('Course:CourseService')->searchCourses(
            $conditions,
            array('createdTime' => 'DESC'),
            $offset,
            $limit
        );

        $total = $this->service('Course:CourseService')->searchCourseCount($conditions);

        $this->getOCUtil()->multiple($courses, array('creator', 'teacherIds'));
        $this->getOCUtil()->multiple($courses, array('courseSetId'), 'courseSet');

        return $this->makePagingObject($courses, $total, $offset, $limit);
    }

    /**
     * @return CourseService
     */
    private function getCourseService()
    {
        return $this->service('Course:CourseService');
    }

    /**
     * @return ClassroomService
     */
    private function getClassroomService()
    {
        return $this->service('Classroom:ClassroomService');
    }

    /**
     * @return MemberService
     */
    protected function getMemberService()
    {
        return $this->service('Course:MemberService');
    }

}