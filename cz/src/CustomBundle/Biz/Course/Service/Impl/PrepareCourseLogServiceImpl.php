<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\PrepareCourseLogService;

class PrepareCourseLogServiceImpl extends BaseService implements PrepareCourseLogService
{
    public function createLog($courseId)
    {
        $log = $this->getLogByCourseId($courseId);
        if (!empty($log)) {
            throw $this->createAccessDeniedException('已经写入该日志。');
        }
        $user = $this->getCurrentUser();
        $course = $this->getCourseService()->getCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException('Course #{$courseId}不存在');
        }

        if ($course['type'] != 'instant') {
            throw $this->createAccessDeniedException('只有实时课程才会记录');
        }

        return $this->getPrepareCourseLogDao()->create(
            array(
                'courseId' => $courseId,
                'termCode' => empty($course['termCode']) ? null : $course['termCode'],
                'userId' => $user['id'],
                'message' => "{$user['truename']}备课《{$course['title']}》",
            )
        );
    }

    public function getLogByCourseId($courseId)
    {
        return $this->getPrepareCourseLogDao()->getByCourseId($courseId);
    }

    public function countCurrentTermLog()
    {
        $term = $this->getCourseService()->getCurrentTerm();

        return $this->countLogByTermCode($term['shortCode']);
    }

    public function countCurrentTermLogByOrgCode($orgCode)
    {
        $term = $this->getCourseService()->getCurrentTerm();

        return $this->countLogByTermCodeAndOrgCode($term['shortCode'], $orgCode);
    }

    public function countLogByTermCodeAndOrgCode($termCode, $orgCode)
    {
        if ($orgCode == '1.') {
            return $this->countLogByTermCode($termCode);
        }

        return $this->getPrepareCourseLogDao()->countByTermCodeAndOrgCode($termCode, $orgCode);
    }

    public function countLogByTermCode($termCode)
    {
        return $this->getPrepareCourseLogDao()->count(array('termCode' => $termCode));
    }

    public function countCurrentTermTeachersByOrgCode($orgCode)
    {
        $term = $this->getCourseService()->getCurrentTerm();

        return $this->getPrepareCourseLogDao()->countCurrentTermTeachersByOrgCode($term['shortCode'], $orgCode);
    }

    protected function getPrepareCourseLogDao()
    {
        return $this->createDao('CustomBundle:Course:PrepareCourseLogDao');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }
}
