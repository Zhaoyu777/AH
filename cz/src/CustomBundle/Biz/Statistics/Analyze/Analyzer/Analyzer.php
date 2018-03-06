<?php

namespace CustomBundle\Biz\Statistics\Analyze\Analyzer;

use Codeages\Biz\Framework\Context\Biz;
use CustomBundle\Biz\Statistics\Analyze\AnalyzerMap;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Analyzer
{
    protected $biz;

    protected $logger;

    public function __construct(Biz $biz, $options)
    {
        $this->init($biz, $options);
    }

    public function init(Biz $biz, $options)
    {
        $this->setBiz($biz);
        $this->setLogger();
        $this->initParameters($options);
    }

    protected function setBiz(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function initParameters($options)
    {
    }

    public function setLogger($name = 'Logger')
    {
        $this->logger = new Logger($name);
        $this->logger->pushHandler(new StreamHandler($this->biz['log_directory'].'/job.log', Logger::DEBUG));
    }

    public function getLogger()
    {
        if (empty($this->logger)) {
            return null;
        }

        return $this->logger;
    }

    public function log($message, $isError = false)
    {
        if ($isError) {
            $this->getLogger()->error($message);
        } else {
            $this->getLogger()->info($message);
        }
    }

    public function getParameter($name)
    {
        return $this->$name;
    }

    protected function getBiz()
    {
        return $this->biz;
    }

    public function excute()
    {
        $this->prepareForAnalyze();
        $this->log('---开始数据分析---');
        $time = time();
        $this->analyze();
        $this->log('---数据分析结束，共耗时'.(time() - $time).'秒---');
        $this->log('---开始插入数据库---');
        $time = time();
        $this->save();
        $this->log('---数据插入结束，共耗时'.(time() - $time).'秒---');
    }

    public function prepareForAnalyze()
    {
    }

    public function analyze()
    {
    }

    public function save()
    {
    }
}
