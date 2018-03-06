<?php

namespace CustomBundle\Biz;

use Pimple\Container;
use Biz\Common\HTMLHelper;
use Pimple\ServiceProviderInterface;
use CustomBundle\Biz\RandomTestpaper\Builder\RandomTestpaperBuilder;

class DefaultServiceProvider implements ServiceProviderInterface
{
    public function register(Container $biz)
    {
        $biz['testpaper_builder.randomTestpaper'] = function ($biz) {
            return new RandomTestpaperBuilder($biz);
        };
    }
}
