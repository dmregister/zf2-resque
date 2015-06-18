<?php

namespace Zf2Resque\Service;

use Resque_Job;
use Zend\ServiceManager\ServiceManager;
use Zf2Resque\Service\ResqueJob;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

class ResqueWorker extends \Resque_Worker implements EventManagerAwareInterface
{
    protected $serviceManager;

    protected $eventManager;

    public function __construct($queues, $serviceManager = null)
    {
        if ($serviceManager !== null)
        {
            $this->setServiceManager($serviceManager);
        }

        parent::__construct($queues);
    }

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }


    /**
     * @param  EventManagerInterface $eventManager
     * @return void
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $eventManager->addIdentifiers(array(
            get_called_class()
        ));

        $this->eventManager = $eventManager;
    }

    /**
     * @return EventManagerInterface
     */
    public function getEventManager()
    {

        return $this->eventManager;
    }


    /**
     * @param  bool            $blocking
     * @param  int             $timeout
     * @return object|boolean               Instance of Resque_Job if a job is found, false if not.
     */
    public function reserve($blocking = false, $timeout = null)
    {
        $queues = $this->queues();
        if (!is_array($queues))
        {
            return;
        }

        if ($blocking === true)
        {
            $job = ResqueJob::reserveBlocking($queues, $timeout);
            if ($job)
            {
                $job->logger = $this->logger;
                $this->logger->log(\Psr\Log\LogLevel::INFO,
                        'Found job on {queue}', array('queue' => $job->queue));
                return $job;
            }
        }
        else
        {
            foreach ($queues as $queue)
            {
                $this->logger->log(\Psr\Log\LogLevel::INFO,
                        'Checking {queue} for jobs', array('queue' => $queue));
                $job = ResqueJob::reserve($queue, $this->getServiceManager());
                if ($job)
                {
                    $job->logger = $this->logger;
                    $this->logger->log(\Psr\Log\LogLevel::INFO,
                            'Found job on {queue}',
                            array('queue' => $job->queue));
                    return $job;
                }
            }
        }

        return false;
    }

    /**
     * Tell Redis which job we're currently working on.
     *
     * @param object $job Resque_Job instance containing the job we're working on.
     */
    public function workingOn(Resque_Job $job)
    {
        $job->worker = $this;
        $this->currentJob = $job;
        $job->updateStatus(\Resque_Job_Status::STATUS_RUNNING);
        $data = json_encode(array(
            'queue' => $job->queue,
            'run_at' => strftime('%a %b %d %H:%M:%S %Z %Y'),
            'payload' => $job->payload
        ));
        \Resque::redis()->set('worker:' . $job->worker, $data);
    }

    /**
     * Schedule a worker for shutdown. Will finish processing the current job
     * and when the timeout interval is reached, the worker will shut down.
     */
    public function shutdown()
    {
        parent::shutdown();


        $this->getEventManager()->trigger('Zf2Resque.shutdown', null, array());

    }
}
