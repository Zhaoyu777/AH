<?php

namespace CustomBundle\Controller\Admin;

use AppBundle\Controller\Admin\DefaultController as BaseDefaultController;

class HeaderController extends BaseDefaultController
{
    public function customHeaderAction()
    {
        return $this->render('admin/header/custom-header.html.twig');
    }
}
