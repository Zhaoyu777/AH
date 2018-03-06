<?php

use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../app/autoload.php';
$request = Request::createFromGlobals();
$kernel = new AppKernel('prod', true);
$kernel->setRequest($request);
$kernel->boot();

$container = $kernel->getContainer();
$container->enterScope('request');
$container->set('request', $request, 'request');

$biz = $kernel->getContainer()->get('biz');
$parameters = file_get_contents(__DIR__.'/../app/config/parameters.yml');
$parameters = \Symfony\Component\Yaml\Yaml::parse($parameters);
$parameters = $parameters['parameters'];

use Pimple\Container;

$options = [
    'server' => [
        'host' => $parameters['beanstalkd']['host'],
        'port' => $parameters['beanstalkd']['port'],
    ],
    'tubes' => [
        'TeacherSignInWorker' => [
            'worker_num' => 5,
            'class' => 'CustomBundle\\Works\\TeacherSignInWorker',
            'timeoutLog' => 2000
        ],
        'TeacherCancelSignInWorker' => [
            'worker_num' => 2,
            'class' => 'CustomBundle\\Works\\TeacherCancelSignInWorker',
            'timeoutLog' => 2000
        ],
        'StudentSignInWorker' => [
            'worker_num' => 10,
            'class' => 'CustomBundle\\Works\\StudentSignInWorker',
            'timeoutLog' => 2000
        ],
        'LessonCancelWorker' => [
            'worker_num' => 2,
            'class' => 'CustomBundle\\Works\\LessonCancelWorker',
            'timeoutLog' => 2000
        ],
        'LessonEndWorker' => [
            'worker_num' => 2,
            'class' => 'CustomBundle\\Works\\LessonEndWorker',
            'timeoutLog' => 2000
        ],
        'WeixinUploadWorker' => [
            'worker_num' => 10,
            'class' => 'CustomBundle\\Works\\WeixinUploadWorker'
        ],
        'MarkUserLoginInfoWorker' => [
            'worker_num' => 1,
            'class' => 'CustomBundle\\Works\\MarkUserLoginInfoWorker'
        ],
    ],

    'log_path' => __DIR__.'/../app/logs/plumber.log',
    'pid_path' =>  __DIR__.'/../app/logs/plumber.pid',
    'biz' => $biz,
];

$container = new Container($options);

return $container;
