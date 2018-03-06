<?php
namespace CustomBundle\Controller\Weixin;

use CustomBundle\Biz\Course\Service;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Biz\Course\Service\MemberService;
use CustomBundle\Biz\Score\Service\ScoreService;

class MyController extends BaseController
{
    public function myAction(Request $request)
    {
        $request = $request->query->all();
        $user = $this->getCurrentUser();

        $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
        $currentTerm = $this->getCourseService()->getCurrentTerm();

        $result = array(
            'truename' => $user['truename'],
            'nickname' => $user['number'],
            'avatar' => $this->getWebExtension()->getFpath($avatar, 'avatar.png'),
        );

        if (!$user->isTeacher()) {
            $scores = $this->getScoreService()->findByTermAndUserId($currentTerm['shortCode'], $user['id']);
            $result['credit'] = $this->sum($scores, 'score');
        } else {
            $result['credit'] = $this->getTeacherScoreService()->getSumScoreByTermAndUserId($currentTerm['shortCode'], $user['id']);
        }

        return $this->createJsonResponse($result);
    }

    public function albumsAction()
    {
        $user = $this->getCurrentUser();

        $contents = $this->getDisplayWallResultService()->findContentsByUserIds(array($user['id']));
        $result = array();
        $date = date('Y-m-d');
        foreach ($contents as $key => $content) {
            $result[$key] = array(
                'id' => $content['id'],
                'uri' => $this->getWebExtension()->getFilePath($content['uri']),
                'month' => date("n", $content['createdTime']).'月',
                'day' => date("d", $content['createdTime']),
                'likeNum' => $content['likeNum'],
                'postNum' => $content['postNum']
            );

            if ($date == date('Y-m-d', $content['createdTime'])) {
                $result[$key]['day'] = '今天';
                $result[$key]['month'] = null;
            }
        }

        return $this->createJsonResponse($result);
    }

    public function scoreAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $currentPage = $request->query->get('page', 1);
        $perPageCount = $request->query->get('perPageCount', 10);

        $currentTerm = $this->getCourseService()->getCurrentTerm();
        $conditions = array(
            'term' => $currentTerm['shortCode'],
            'userId' => $user['id'],
            'minScore' => 1,
        );
        $scoreCount = $this->getScoreService()->countScores($conditions);
        $scores = $this->getScoreService()->searchScores(
            $conditions,
            array('createdTime' => 'DESC'),
            ($currentPage - 1) * $perPageCount,
            $perPageCount
        );

        $credit = $this->getScoreService()->sumScoresByTermAndUserId($currentTerm['shortCode'], $user['id']);
        $courseIds = ArrayToolkit::column($scores, 'courseId');

        $courses = $this->getCourseService()->findCoursesByIds($courseIds);
        $courseSets = $this->getCourseSetService()->findCourseSetsByCourseIds($courseIds);

        $result = array();
        foreach ($scores as $key => $score) {
            if ($score['score'] != 0) {
                $result[$key]['courseTitle'] = $courseSets[$courses[$score['courseId']]['courseSetId']]['title'];
                $result[$key]['activityTitle'] = $score['remark'];
                $result[$key]['updatedTime'] = date("Y-m-d H:i:s", $score['createdTime']);
                $result[$key]['score'] = $score['score'];
            }
        }

        return $this->createJsonResponse(array(
            'credit' => $credit,
            'result' => $result,
            'pageCount' => ceil($scoreCount / $perPageCount),
        ));
    }

    public function teacherScoreAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $currentTerm = $this->getCourseService()->getCurrentTerm();
        $currentPage = $request->query->get('page', 1);
        $perPageCount = $request->query->get('perPageCount', 10);
        $conditions = array(
            'term' => $currentTerm['shortCode'],
            'userId' => $user['id'],
        );

        $scoreCount = $this->getTeacherScoreService()->countTeacherScores($conditions);
        $scores = $this->getTeacherScoreService()->searchTeacherScores(
            $conditions,
            array('createdTime' => 'DESC'),
            ($currentPage - 1) * $perPageCount,
            $perPageCount
        );

        $result = array();
        foreach ($scores as $key => $score) {
            if ($score['score'] != 0) {
                $result[$key]['remark'] = $score['remark'];
                $result[$key]['createdTime'] = date("Y-m-d H:i:s", $score['createdTime']);
                $result[$key]['score'] = $score['score'];
            }
        }

        return $this->createJsonResponse(array(
            'credit' => $this->getTeacherScoreService()->getSumScoreByTermAndUserId($currentTerm['shortCode'], $user['id']),
            'result' => $result,
            'pageCount' => ceil($scoreCount / $perPageCount),
        ));
    }

    protected function sum($array, $field)
    {
        $sum = 0;
        foreach ($array as $key => $value) {
            $sum += $value[$field];
        }

        return $sum;
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getRollcallResultService()
    {
        return $this->createService('CustomBundle:Activity:RollcallResultService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getScoreService()
    {
        return $this->createService('CustomBundle:Score:ScoreService');
    }

    protected function getDisplayWallResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getTeacherScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:TeacherScoreService');
    }
}
