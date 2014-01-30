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
	                        'controller' => 'Zf2Resque\Controller\Cli',
	                        'action'     => 'start',
	                    ),
	                ),
	            ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Zf2Resque\Controller\Cli' => 'Zf2Resque\Controller\CliController'
        ),
    )
);