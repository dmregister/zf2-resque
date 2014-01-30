<?php

return array(
	'console' => array(
        'router' => array(
            'routes' => array(
            	'start-resque' => array(
	                'type' => 'Zend\Mvc\Router\Console\Simple',
	                'options' => array(
	                    'route'    => 'start resque',
	                    'defaults' => array(
	                        'controller' => 'WorkerQueue\Controller\Cli',
	                        'action'     => 'start',
	                    ),
	                ),
	            ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'WorkerQueue\Controller\Cli' => 'WorkerQueue\Controller\CliController'
        ),
    )
);