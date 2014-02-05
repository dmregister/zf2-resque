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

class CliController extends AbstractActionController
{

    public function startAction()
    {
        $config = $this->getServiceLocator()->get('config');
        
        $REDIS_BACKEND = $config['zf2resque']['redisBackend'];
        $REDIS_BACKEND_DB = getenv('REDIS_BACKEND_DB');
        if (!empty($REDIS_BACKEND))
        {
            if (empty($REDIS_BACKEND_DB))
                \Resque::setBackend($REDIS_BACKEND);
            else
                \Resque::setBackend($REDIS_BACKEND, $REDIS_BACKEND_DB);
        }

        $logLevel = false;
        $LOGGING = getenv('LOGGING');
        $VERBOSE = getenv('VERBOSE');
        $VVERBOSE = getenv('VVERBOSE');
        if (!empty($LOGGING) || !empty($VERBOSE))
        {
            $logLevel = true;
        }
        else if (!empty($VVERBOSE))
        {
            $logLevel = true;
        }

        $logger = new \Resque_Log($logLevel);

        $BLOCKING = getenv('BLOCKING') !== FALSE;

        $interval = 5;
        $INTERVAL = getenv('INTERVAL');
        if (!empty($INTERVAL))
        {
            $interval = $INTERVAL;
        }

        $count = 1;
        $COUNT = getenv('COUNT');
        if (!empty($COUNT) && $COUNT > 1)
        {
            $count = $COUNT;
        }

        $PREFIX = getenv('PREFIX');
        if (!empty($PREFIX))
        {
            $logger->log(Psr\Log\LogLevel::INFO, 'Prefix set to {prefix}',
                    array('prefix' => $PREFIX));
            Resque_Redis::prefix($PREFIX);
        }

        if ($count > 1)
        {
            for ($i = 0; $i < $count; ++$i)
            {
                $pid = Resque::fork();
                if ($pid == -1)
                {
                    $logger->log(Psr\Log\LogLevel::EMERGENCY,
                            'Could not fork worker {count}',
                            array('count' => $i));
                    die();
                }
                // Child, start the worker
                else if (!$pid)
                {
                    $worker = $this
                            ->getServiceLocator()
                            ->get('Zf2Resque\Service\ResqueWorker');
                    $worker->setLogger($logger);
                    $logger->log(Psr\Log\LogLevel::NOTICE,
                            'Starting worker {worker}',
                            array('worker' => $worker));
                    $worker->work($interval, $BLOCKING);
                    break;
                }
            }
        }
        // Start a single worker
        else
        {

            $worker = $this
                    ->getServiceLocator()
                    ->get('Zf2Resque\Service\ResqueWorker');
            $worker->setLogger($logger);

            $PIDFILE = getenv('PIDFILE');
            if ($PIDFILE)
            {
                file_put_contents($PIDFILE, getmypid()) or
                        die('Could not write PID information to ' . $PIDFILE);
            }

            $logger->log(\Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}',
                    array('worker' => $worker));
            $worker->work($interval, $BLOCKING);
        }
    }

    public function addJobAction()
    {
        date_default_timezone_set('GMT');
        \Resque::setBackend('127.0.0.1:6379');

        $args = array(
            'time' => time(),
            'array' => array(
                'test' => 'test',
            ),
        );

        $jobId = \Resque::enqueue('email', 'Zf2Resque\Job\Sample', $args, true);
        echo "Queued job " . $jobId . "\n\n";

        $jobId = \Resque::enqueue('email', 'Zf2Resque\Job\Sample', $args,
                        true);
        echo "Queued job " . $jobId . "\n\n";

        return;
    }

}