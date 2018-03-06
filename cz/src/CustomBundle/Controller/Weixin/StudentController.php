<?php
namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Biz\User\Service\TokenService;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class StudentController extends BaseController
{
    public function studentsAction(Request $request, $courseId)
    {
        $data = $request->query->all();
        $total = $this->getMemberService()->getCourseStudentCount($courseId);
        $page = isset($data['page']) ? $data['page'] : 1;
        $limit = isset($data['limit']) ? $data['limit'] : 20;

        $course = $this->getCourseService()->getCourse($courseId);

        $start = ($page - 1) * $limit;

        $students = $this->getMemberService()->fintStudentsByCourseIdWithSocre($courseId, $start, $limit);
        $userIds = ArrayToolkit::column($students, 'userId');

        $users = $this->getUserService()->findUsersByIds($userIds);

        $result = array();
        foreach ($students as $key => $student) {
            $result[] = array(
                'truename' => $users[$student['userId']]['truename'],
                'nickname' => $users[$student['userId']]['nickname'],
                'number' => $users[$student['userId']]['number'],
                'credit' => $student['scores'] ? $student['scores'] : 0,
                'avatar' => $this->getWebExtension()->getFpath($users[$student['userId']]['smallAvatar'], 'avatar.png')
            );
        }

        $paging = array(
            'total' => ceil($total/$limit),
            'page' => $page,
            'limit' => $limit
        );

        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->createJsonResponse(array(
            'paging' => $paging,
            'members' => $result,
            'count' => $total,
            'courseTitle' => $courseSet['title'],
        ));
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getScoreService()
    {
        return $this->createService('CustomBundle:Score:ScoreService');
    }
}
