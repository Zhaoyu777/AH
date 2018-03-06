<?php
namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Controller\Weixin\WeixinBaseController;

class LessonEvaluationController extends WeixinBaseController
{
    public function evaluationAction(Request $request, $courseId, $lessonId)
    {
        $fields = $request->query->all();
        $fields['courseId'] = $courseId;
        $fields['lessonId'] = $lessonId;

        $evaluation = $this->getLessonEvaluationService()->createEvaluation($fields);
        $result = isset($evaluation['id']) ? true :  $evaluation;

        return $this->createJsonResponse($result);
    }

    public function getLessonEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }
}
