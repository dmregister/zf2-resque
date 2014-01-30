<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zf2Resque\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CliController extends AbstractActionController {

    public function startAction() {

        $QUEUE = 'emails';
        if (empty($QUEUE)) {
            die("Set QUEUE env var containing the list of queues to work.\n");
        }

        $REDIS_BACKEND = '10.208.141.103:6379';
        $REDIS_BACKEND_DB = getenv('REDIS_BACKEND_DB');
        if (!empty($REDIS_BACKEND)) {
            if (empty($REDIS_BACKEND_DB))
                \Resque::setBackend($REDIS_BACKEND);
            else
                \Resque::setBackend($REDIS_BACKEND, $REDIS_BACKEND_DB);
        }

        $logLevel = false;
        $LOGGING = getenv('LOGGING');
        $VERBOSE = getenv('VERBOSE');
        $VVERBOSE = 1;
        if (!empty($LOGGING) || !empty($VERBOSE)) {
            $logLevel = true;
        } else if (!empty($VVERBOSE)) {
            $logLevel = true;
        }

        $APP_INCLUDE = getenv('APP_INCLUDE');
        if ($APP_INCLUDE) {
            if (!file_exists($APP_INCLUDE)) {
                die('APP_INCLUDE (' . $APP_INCLUDE . ") does not exist.\n");
            }

            require_once $APP_INCLUDE;
        }

        // See if the APP_INCLUDE containes a logger object,
        // If none exists, fallback to internal logger
        if (!isset($logger) || !is_object($logger)) {
            $logger = new \Resque_Log($logLevel);
        }

        $BLOCKING = getenv('BLOCKING') !== FALSE;

        $interval = 5;
        $INTERVAL = getenv('INTERVAL');
        if (!empty($INTERVAL)) {
            $interval = $INTERVAL;
        }

        $count = 1;
        $COUNT = getenv('COUNT');
        if (!empty($COUNT) && $COUNT > 1) {
            $count = $COUNT;
        }

        $PREFIX = getenv('PREFIX');
        if (!empty($PREFIX)) {
            $logger->log(Psr\Log\LogLevel::INFO, 'Prefix set to {prefix}', array('prefix' => $PREFIX));
            Resque_Redis::prefix($PREFIX);
        }

        if ($count > 1) {
            for ($i = 0; $i < $count; ++$i) {
                $pid = Resque::fork();
                if ($pid == -1) {
                    $logger->log(Psr\Log\LogLevel::EMERGENCY, 'Could not fork worker {count}', array('count' => $i));
                    die();
                }
                // Child, start the worker
                else if (!$pid) {
                    $queues = explode(',', $QUEUE);
                    $worker = new Resque_Worker($queues);
                    $worker->setLogger($logger);
                    $logger->log(Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
                    $worker->work($interval, $BLOCKING);
                    break;
                }
            }
        }
        // Start a single worker
        else {
            $queues = explode(',', $QUEUE);
            $worker = new \Resque_Worker($queues, $this->getServiceLocator());
            $worker->setLogger($logger);

            $PIDFILE = getenv('PIDFILE');
            if ($PIDFILE) {
                file_put_contents($PIDFILE, getmypid()) or
                        die('Could not write PID information to ' . $PIDFILE);
            }

            $logger->log(\Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
            $worker->work($interval, $BLOCKING);
        }
    }

}