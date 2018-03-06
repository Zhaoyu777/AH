<?php

namespace CustomBundle\Biz\Activity\Strategy;

use CustomBundle\Biz\Activity\Strategy\Impl\NoneStrategy;
use CustomBundle\Biz\Activity\Strategy\Impl\FixedGroupStrategy;
use CustomBundle\Biz\Activity\Strategy\Impl\FixedPersonStrategy;
use CustomBundle\Biz\Activity\Strategy\Impl\RandomGroupStrategy;
use CustomBundle\Biz\Activity\Strategy\Impl\RandomPersonStrategy;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;

class StrategyContext
{
    /**
     * groupWay_submitWay
     */
    const NONE_PERSON_STRATEGY = 'none_person';
    const NONE_GROUP_STRATEGY = 'none_group';
    const FIXED_GROUP_STRATEGY = 'fixed_group';
    const FIXED_PERSON_TRATEGY = 'fixed_person';
    const RANDOM_GROUP_TRATEGY = 'random_group';
    const RANDOM_PERSON_TRATEGY = 'random_person';

    private $strategyMap = array();

    private static $_instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function createStrategy($specificActivity, $biz, $container)
    {
        $strategyType = $specificActivity['groupWay'].'_'.$specificActivity['submitWay'];
        if (!empty($this->strategyMap[$strategyType])) {
            return $this->strategyMap[$strategyType];
        }

        switch ($strategyType) {
            case self::NONE_PERSON_STRATEGY:
                $this->strategyMap[self::NONE_PERSON_STRATEGY] = new NoneStrategy($biz, $container);
                break;
            case self::NONE_GROUP_STRATEGY:
                $this->strategyMap[self::NONE_GROUP_STRATEGY] = new NoneStrategy($biz, $container);
                break;
            case self::FIXED_GROUP_STRATEGY:
                $this->strategyMap[self::FIXED_GROUP_STRATEGY] = new FixedGroupStrategy($biz, $container);
                break;
            case self::FIXED_PERSON_TRATEGY:
                $this->strategyMap[self::FIXED_PERSON_TRATEGY] = new FixedPersonStrategy($biz, $container);
                break;
            case self::RANDOM_GROUP_TRATEGY:
                $this->strategyMap[self::RANDOM_GROUP_TRATEGY] = new FixedGroupStrategy($biz, $container);
                break;
            case self::RANDOM_PERSON_TRATEGY:
                $this->strategyMap[self::RANDOM_PERSON_TRATEGY] = new FixedPersonStrategy($biz, $container);
                break;
            default:
                throw new NotFoundException('teach method strategy does not exist');
        }

        return $this->strategyMap[$strategyType];
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this->strategy, $name)) {
            throw new \Exception('method not exists.');
        }

        return call_user_func_array(array($this->strategy, $name), $arguments);
    }
}
