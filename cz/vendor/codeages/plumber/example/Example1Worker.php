<?php

namespace Codeages\Plumber\Example;

use Codeages\Plumber\IWorker;
use Codeages\Plumber\ContainerAwareTrait;

class Example1Worker implements IWorker
{
    use ContainerAwareTrait;

    public function execute($data)
    {
        $this->logger->info("I'm example 1 worker.");

        return array('code' => IWorker::FINISH);
    }

}
