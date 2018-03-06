<?php

namespace CustomBundle\Common;

use Codeages\Beanstalk\Client;

use Topxia\Service\Common\ServiceKernel;

class BeanstalkClient
{
    public static function putTubeMessage($tube, $data)
    {
        $parameters = ServiceKernel::instance()->getParameter('beanstalkd');

        $config = [];
        $config['host'] = $parameters['host'];
        $config['port'] = $parameters['port'];
        $config['persistent'] = false;

        $beanstalk = new Client($config);

        $beanstalk->connect();
        $beanstalk->useTube($tube);
        $data['queueId'] = uniqid(md5(gethostname()));
        $message = json_encode($data);

        $result = $beanstalk->put(
            500, // Give the job a priority of 23.
            0,  // Do not wait to put job into the ready queue.
            60, // Give the job 1 minute to run.
            $message // The job's .body
        );

        $beanstalk->disconnect();
    }
}
