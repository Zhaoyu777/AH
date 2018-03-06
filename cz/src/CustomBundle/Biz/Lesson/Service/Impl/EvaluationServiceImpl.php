<?php

namespace CustomBundle\Biz\Lesson\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Lesson\Service\EvaluationService;

class EvaluationServiceImpl extends BaseService implements EvaluationService
{
    public function tryEvaluate($evaluation)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('未登陆');
        }

        $lesson = $this->getLessonService()->getCourseLesson($evaluation['lessonId']);
        if ($lesson['status'] != 'teached') {
            throw $this->createAccessDeniedException('未下课');
        }

        $member = $this->getMemberService()->getCourseMember($evaluation['courseId'], $user['id']);

        if (empty($member) || $member['role'] != 'student') {
            throw $this->createAccessDeniedException('非该课程学员');
        }

        $evaluation = $this->getEvaluationByLessonIdAndUserId($evaluation['lessonId'], $user['id']);
        if (!empty($evaluation)) {
            throw $this->createAccessDeniedException('已评价');
        }

        return true;
    }

    public function createEvaluation($evaluation)
    {
        if (!ArrayToolkit::requireds($evaluation, array('courseId', 'lessonId', 'remark', 'score'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $user = $this->getCurrentUser();

        $this->tryEvaluate($evaluation);

        $evaluation = ArrayToolkit::parts($evaluation, array(
            'courseId',
            'lessonId',
            'remark',
            'score',
        ));
        $evaluation['studentId'] = $user['id'];

        $created = $this->getEvaluationDao()->create($evaluation);

        return $created;
    }

    public function findEvaluationsByCourseIdAndUserId($courseId, $userId)
    {
        return $this->getEvaluationDao()->findByCourseIdAndUserId($courseId, $userId);
    }

    public function getEvaluationByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->getEvaluationDao()->getByLessonIdAndUserId($lessonId, $userId);
    }

    public function findEvaluationsByLessonId($lessonId)
    {
        return $this->getEvaluationDao()->findByLessonId($lessonId);
    }

    public function getScoreAvgByLessonId($lessonId)
    {
        $avgScore = $this->getEvaluationDao()->getScoreAvgByLessonId($lessonId);

        return empty($avgScore) ? 0 : $avgScore;
    }

    public function findEvaluationsByCourseId($courseId)
    {
        return $this->getEvaluationDao()->findByCourseId($courseId);
    }

    public function getCourseAverageByUserId($courseId, $userId)
    {
        return $this->getEvaluationDao()->getCourseAverageByUserId($courseId, $userId);
    }

    public function getEvaluation($id)
    {
        return $this->getEvaluationDao()->get($id);
    }

    protected function getEvaluationDao()
    {
        return $this->createDao('CustomBundle:Lesson:EvaluationDao');
    }

    protected function getCourseDao()
    {
        return $this->createDao('CustomBundle:Course:CourseDao');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
