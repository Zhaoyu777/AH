<?php

namespace Biz\Sms\SmsProcessor;

use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;
use Topxia\Service\Common\ServiceKernel;

class SmsProcessorFactory
{
    /**
     * @param $targetType
     *
     * @return BaseSmsProcessor
     *
     * @throws InvalidArgumentException
     */
    public static function create($targetType)
    {
        if (empty($targetType)) {
            throw new InvalidArgumentException('短信类型不存在');
        }

        $class = __NAMESPACE__.'\\'.ucfirst($targetType).'SmsProcessor';

        return new $class(ServiceKernel::instance()->getBiz());
    }
}
