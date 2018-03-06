<?php

namespace CustomBundle\Controller\CourseStatistics;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class TeacherCourseStatisticsController extends BaseController
{
    public function courseStatisticsAction()
    {
        $user = $this->getCurrentUser();
        $termCode = $this->getCourseService()->getCurrentTerm();

        $statistics = $this->getTeacherCourseStatisticsService()->getStatisticsByUserIdAndTermCode($user['id'], $termCode['shortCode']);

        $percentage = $this->getTeacherCourseStatisticsService()->getStatisticsPercentageByUserIdAndTermCode($user['id'], $termCode['shortCode']);

        return $this->render('my/teached-statistics/my-teached-statistics.html.twig', array(
            'statistics' => $statistics,
            'percentage' => $percentage,
            'termCode' => $termCode,
        ));
    }

    protected function getTeacherCourseStatisticsService()
    {
        return $this->getBiz()->service('CustomBundle:Statistics:TeacherCourseStatisticsService');
    }

    protected function getFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }

    protected function gerMaterialService()
    {
        return $this->getBiz()->service('CustomBundle:Course:MaterialService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseService');
    }
}
