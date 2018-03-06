<?php

namespace Biz\Mail;

use AppBundle\Common\SettingToolkit;
use Codeages\Biz\Framework\Context\BizAware;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException;

abstract class Mail extends BizAware
{
    private $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function __set($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function __get($name)
    {
        if ('options' === $name) {
            return $this->options;
        }

        if (!array_key_exists($name, $this->options)) {
            return null;
        }

        return $this->options[$name];
    }

    public function __isset($name)
    {
        if ('options' === $name) {
            return $this->options !== null;
        }

        return isset($this->options[$name]);
    }

    public function __unset($name)
    {
        unset($this->options[$name]);

        return $this;
    }

    protected function parseTemplate($templateName)
    {
        return $this->biz['email_template_parser']->parseTemplate($templateName, $this->options);
    }

    protected function setting($name, $default = '')
    {
        return SettingToolkit::getSetting($name, $default);
    }

    public function send()
    {
        $this->mailCheckRatelimiter();

        return $this->doSend();
    }

    protected function mailCheckRatelimiter()
    {
        $factory = $this->biz['ratelimiter.factory'];
        $limiter = $factory('email_'.$this->options['template'], 5, 1800);
        $remain = $limiter->check($this->to);
        if ($remain == 0) {
            throw new AccessDeniedException('操作过于频繁，请30分钟之后再试');
        }
    }

    abstract public function doSend();
}
