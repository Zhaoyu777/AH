<?php

namespace CustomBundle\Common\Platform;

use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;

class PlatformFactory
{
    private static $clients = array();

    public static function create($biz)
    {
        $type = 'QiyeWeixin';
        if (empty(self::$clients[$type])) {
            $class = __NAMESPACE__."\\{$type}Client";
            $option = array(
                'host' => '',
                'type' => $type,
                'entry' => '/weixin/index.html',
            );
            switch ($type) {
                case 'QiyeWeixin':
                    if (!empty($_SERVER['HTTP_HOST'])) {
                        $option['host'] = 'http://'.$_SERVER['HTTP_HOST'];
                    }
                    break;

                default:
                    throw new InvalidArgumentException('平台类型错误');
            }

            self::$clients[$type] = new $class($option);
            self::$clients[$type]->setBiz($biz);
        }

        return self::$clients[$type];
    }
}