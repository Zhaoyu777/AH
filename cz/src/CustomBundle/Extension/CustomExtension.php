<?php

namespace CustomBundle\Extension;

use Pimple\Container;
use Topxia\Service\Common\ServiceKernel;
use CustomBundle\Biz\Activity\Type\Rollcall;
use CustomBundle\Biz\Activity\Type\DisplayWall;
use CustomBundle\Biz\Activity\Type\OneSentence;
use CustomBundle\Biz\Activity\Type\RaceAnswer;
use CustomBundle\Biz\Activity\Type\Questionnaire;
use CustomBundle\Biz\Activity\Type\Interval;
use CustomBundle\Biz\Activity\Type\BrainStorm;
use CustomBundle\Biz\Activity\Type\RandomTestpaper;
use CustomBundle\Biz\Activity\Type\PracticeWork;
use CustomBundle\Biz\Activity\Type\Practice;
use AppBundle\Common\ArrayToolkit;
use Pimple\ServiceProviderInterface;
use Codeages\Biz\Framework\Context\Biz;
use Biz\Testpaper\Pattern\QuestionTypePattern;
use AppBundle\Extension\Extension;

class CustomExtension extends Extension implements ServiceProviderInterface
{
    public function getActivities()
    {
        $biz = $this->biz;

        return array(
            'text' => array(
                'meta' => array(
                    'name' => 'course.activity.text',
                    'icon' => 'es-icon es-icon-graphicclass',
                ),
                'controller' => 'CustomBundle:Activity/Text',
                'visible' => function ($courseSet, $course) {
                    return $courseSet['type'] != 'live';
                },
            ),
            'video' => array(
                'meta' => array(
                    'name' => 'course.activity.video',
                    'icon' => 'es-icon es-icon-videoclass',
                ),
                'controller' => 'CustomBundle:Activity/Video',
                'visible' => function ($courseSet, $course) {
                    return $courseSet['type'] != 'live';
                },
            ),
            'audio' => array(
                'meta' => array(
                    'name' => 'course.activity.audio',
                    'icon' => 'es-icon es-icon-audioclass',
                ),
                'controller' => 'CustomBundle:Activity/Audio',
                'visible' => function ($courseSet, $course) {
                    return $courseSet['type'] != 'live';
                },
            ),
            'testpaper' => array(
                'meta' => array(
                    'name' => 'course.activity.testpaper',
                    'icon' => 'es-icon es-icon-kaoshi',
                ),
                'controller' => 'CustomBundle:Activity/Testpaper',
                'visible' => function ($courseSet, $course) use ($biz) {
                    return true;
                },
            ),
            'rollcall' => array(
                'meta' => array(
                    'name' => 'course.activity.rollcall',
                    'icon' => 'cz-icon cz-icon-dianmingdati',
                ),
                'controller' => 'CustomBundle:Activity/Rollcall',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
            'practiceWork' => array(
                'meta' => array(
                    'name' => 'course.activity.practice_work',
                    'icon' => 'cz-icon cz-icon-practiceWork',
                ),
                'controller' => 'CustomBundle:Activity/PracticeWork',
                'visible' => function ($courseSet, $course) use ($biz) {
                    return $course['type'] == 'instant';
                },
            ),
            'displayWall' => array(
                'meta' => array(
                    'name' => 'course.activity.displayWall',
                    'icon' => 'cz-icon cz-icon-zhanshiqiang',
                ),
                'controller' => 'CustomBundle:Activity/DisplayWall',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
            'oneSentence' => array(
                'meta' => array(
                    'name' => 'course.activity.oneSentence',
                    'icon' => 'cz-icon cz-icon-yijuhuawenda',
                ),
                'controller' => 'CustomBundle:Activity/OneSentence',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
            'raceAnswer' => array(
                'meta' => array(
                    'name' => 'course.activity.raceAnswer',
                    'icon' => 'cz-icon cz-icon-qiangda',
                ),
                'controller' => 'CustomBundle:Activity/RaceAnswer',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
            'doc' => array(
                'meta' => array(
                    'name' => 'course.activity.doc',
                    'icon' => 'es-icon es-icon-description',
                ),
                'controller' => 'CustomBundle:Activity/Doc',
                'visible' => function ($courseSet, $course) use ($biz) {
                    $storage = $biz->service('System:SettingService')->get('storage');
                    $uploadMode = ArrayToolkit::get($storage, 'upload_mode', 'local');

                    return $uploadMode == 'cloud' && $courseSet['type'] != 'live';
                },
            ),
            'ppt' => array(
                'meta' => array(
                    'name' => 'PPT',
                    'icon' => 'es-icon es-icon-pptclass',
                ),
                'controller' => 'CustomBundle:Activity/Ppt',
                'visible' => function ($courseSet, $course) use ($biz) {
                    $storage = $biz->service('System:SettingService')->get('storage');
                    $uploadMode = ArrayToolkit::get($storage, 'upload_mode', 'local');

                    return $uploadMode == 'cloud' && $courseSet['type'] != 'live';
                },
            ),
            'homework' => array(
                'meta' => array(
                    'name' => 'course.activity.custom.homework',
                    'icon' => 'es-icon es-icon-zuoye',
                ),
                'controller' => 'CustomBundle:Activity/Homework',
                'visible' => function ($courseSet, $course) use ($biz) {
                    return true;
                },
            ),
            'download' => array(
                'meta' => array(
                    'name' => 'course.activity.download',
                    'icon' => 'es-icon es-icon-filedownload',
                ),
                'controller' => 'CustomBundle:Activity/Download',
                'visible' => function ($courseSet, $course) {
                    return true;
                },
            ),
            'interval' => array(
                'meta' => array(
                    'name' => 'course.activity.interval',
                    'icon' => 'cz-icon cz-icon-kejianxiuxi',
                ),
                'controller' => 'CustomBundle:Activity/Interval',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
            'questionnaire' => array(
                'meta' => array(
                    'name' => 'course.activity.questionnaire',
                    'icon' => 'cz-icon cz-icon-tiaocha',
                ),
                'controller' => 'CustomBundle:Activity/Questionnaire',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
            'brainStorm' => array(
                'meta' => array(
                    'name' => 'course.activity.brainStorm',
                    'icon' => 'cz-icon cz-icon-tounaofengbao',
                ),
                'controller' => 'CustomBundle:Activity/BrainStorm',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
            'randomTestpaper' => array(
                'meta' => array(
                    'name' => 'course.activity.randomTestpaper',
                    'icon' => 'es-icon es-icon-kaoshi',
                ),
                'controller' => 'CustomBundle:Activity/RandomTestpaper',
                'visible' => function ($courseSet, $course) {
                    return true;
                },
            ),
            'practice' => array(
                'meta' => array(
                    'name' => 'course.activity.practice',
                    'icon' => 'cz-icon cz-icon-practice',
                ),
                'controller' => 'CustomBundle:Activity/Practice',
                'visible' => function ($courseSet, $course) {
                    return $course['type'] == 'instant';
                },
            ),
        );
    }

    public function register(Container $container)
    {
        $container['activity_type.practice'] = function ($biz) {
            return new Practice($biz);
        };
        
        $container['activity_type.practiceWork'] = function ($biz) {
            return new PracticeWork($biz);
        };

        $container['activity_type.rollcall'] = function ($biz) {
            return new Rollcall($biz);
        };

        $container['activity_type.displayWall'] = function ($biz) {
            return new DisplayWall($biz);
        };

        $container['activity_type.oneSentence'] = function ($biz) {
            return new OneSentence($biz);
        };

        $container['activity_type.raceAnswer'] = function ($biz) {
            return new RaceAnswer($biz);
        };

        $container['activity_type.questionnaire'] = function ($biz) {
            return new Questionnaire($biz);
        };

        $container['activity_type.interval'] = function ($biz) {
            return new Interval($biz);
        };

        $container['activity_type.brainStorm'] = function ($biz) {
            return new BrainStorm($biz);
        };

        $container['activity_type.randomTestpaper'] = function ($biz) {
            return new RandomTestpaper($biz);
        };
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
