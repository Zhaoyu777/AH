<?php

namespace CustomBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Controller\DefaultController as BaseDefaultController;
use CustomBundle\Biz\Course\Service\CourseService;

class DefaultController extends BaseDefaultController
{
    public function helloAction()
    {
        $this->getCustomCourseService()->addActivityPracticeFile(1);
        var_dump(1);exit;
        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();
        $items = $builder->buildItems(84);
        var_dump($items);exit();
        return $this->render('CustomBundle:custom:index.html.twig');
        //  return $this->render('default/index.html.twig');
    }

    /**
     * 使用自己的业务
     * @return CourseService
     */
    protected function getCustomCourseService()
    {
        return $this->getBiz()->service('CustomBundle:File:UploadFileService');
    }

    /**
     * @return CourseService
     */
    protected function getRandomTestpaperService()
    {
        return $this->getBiz()->service('CustomBundle:RandomTestpaper:RandomTestpaperService');
    }
}
