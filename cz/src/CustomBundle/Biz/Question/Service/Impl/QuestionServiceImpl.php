<?php

namespace CustomBundle\Biz\Question\Service\Impl;

use Biz\Question\Service\Impl\QuestionServiceImpl as BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;

class QuestionServiceImpl extends BaseService
{
    protected function getQuestionDao()
    {
        return $this->createDao('CustomBundle:Question:QuestionDao');
    }
}
