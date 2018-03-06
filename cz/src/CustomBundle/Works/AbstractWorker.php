<?php

namespace CustomBundle\Works;

use Monolog\Logger;
use Codeages\Plumber\IWorker;
use Monolog\Handler\StreamHandler;
use Codeages\Biz\Framework\Event\Event;
use Codeages\Plumber\ContainerAwareTrait;

abstract class AbstractWorker implements IWorker
{
    use ContainerAwareTrait;

    private $workerLogger;

    public $type;

    public function execute($data)
    {
        $startTime = $this->getMicroTime();
        $this->reconnect();
        try {
            $result = $this->executeProcess($data);
        } catch (\Exception $e) {
            $this->getWorkerLogger()->error($e);
            $result = array('code' => IWorker::FINISH);
        }
        $endTime = $this->getMicroTime();
        $spendTime = $endTime - $startTime;
        $this->logTimeout($spendTime, $data);

        return $result;
    }

    private function logTimeout($spendTime, $data)
    {
        $workerName = $this->getWorkerName();
        $container = $this->container;
        if (!empty($container['tubes'][$workerName])) {
            $tube = $container['tubes'][$workerName];
            if (isset($tube['timeoutLog'])) {
                if ($spendTime > $tube['timeoutLog']) {
                    $data = json_encode($data);
                    $this->getWorkerLogger()->info("花费时间：{$spendTime}毫秒，worker为：{$workerName}，数据为：{$data}");
                }
            }
        }
    }

    abstract public function executeProcess($data);

    protected function getBiz()
    {
        $container = $this->container;

        return $container['biz'];
    }

    final protected function createService($alias)
    {
        $biz = $this->getBiz();

        return $biz->service($alias);
    }

    public function reconnect()
    {
        if ($this->getBiz()['db']->ping() === false) {
            $this->logger->info('数据库连接超时，重新连接');
            $this->getBiz()['db']->close();
            $this->getBiz()['db']->connect();
        }
    }

    private function getDispatcher()
    {
        return $this->getBiz()['dispatcher'];
    }

    protected function dispatchEvent($eventName, $subject, $arguments = array())
    {
        if ($subject instanceof Event) {
            $event = $subject;
        } else {
            $event = new Event($subject, $arguments);
        }

        return $this->getDispatcher()->dispatch($eventName, $event);
    }

    protected function getWorkerLogger($name = 'worker')
    {
        if ($this->workerLogger) {
            return $this->workerLogger;
        }

        $this->workerLogger = new Logger($name);
        $this->workerLogger->pushHandler(new StreamHandler($this->getBiz()['log_directory'].'/worker.log', Logger::DEBUG));

        return $this->workerLogger;
    }

    protected function getWorkerName()
    {
        $classArr = explode('\\', get_class($this));
        $class = end($classArr);

        return $class;
    }

    protected function getMicroTime()
    {
       list($msec, $sec) = explode(' ', microtime());
       $microtime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

       return $microtime;
    }
}
