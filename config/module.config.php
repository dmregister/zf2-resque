<?php

return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'start-resque' => array(
                    'type' => 'Zend\Mvc\Router\Console\Simple',
                    'options' => array(
                        'route' => 'start resque',
                        'defaults' => array(
                            'controller' => 'Zf2Resque\Controller\Cli',
                            'action' => 'start',
                        ),
                    ),
                ),
                'add-job' => array(
                    'type' => 'Zend\Mvc\Router\Console\Simple',
                    'options' => array(
                        'route' => 'add job',
                        'defaults' => array(
                            'controller' => 'Zf2Resque\Controller\Cli',
                            'action' => 'add-job',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'zf2resque' => array(
        'queues' => array(
            'email'
        )
    ),
    'localVar' => 'working',
    'service_manager' => array(
        'invokables' => array()
    ),
    'controllers' => array(
        'invokables' => array(
            'Zf2Resque\Controller\Cli' => 'Zf2Resque\Controller\CliController'
        ),
    )
);