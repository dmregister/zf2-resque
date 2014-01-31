<?php

namespace Zf2Resque;

class Module {

    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {
        
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                'Zf2Resque\Service\ResqueWorker' => function ($sm) {
                    
                    $config = $sm->get('config');
            
                    return new \Zf2Resque
                        \Service
                        \ResqueWorker($config['zf2resque']['queues'],$sm);
                }
            ),
        );
    }

}