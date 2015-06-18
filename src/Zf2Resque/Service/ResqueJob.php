<?php

namespace Zf2Resque\Service;

use Zend\ServiceManager\ServiceManager;

class ResqueJob extends \Resque_Job
{

    protected $serviceManager;

    /**
     * @var object Instance of the class performing work for this job.
     */
    protected $instance;

    public function __construct($queue, $payload, $serviceManager = null)
    {
        if ($serviceManager !== null)
        {
            $this->setServiceManager($serviceManager);
        }

        parent::__construct($queue, $payload);
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
     * Find the next available job from the specified queue and return an
     * instance of Resque_Job for it.
     *
     * @param string $queue The name of the queue to check for a job in.
     * @return null|object Null when there aren't any waiting jobs, instance of Resque_Job when a job was found.
     */
    public static function reserve($queue, $sm = null)
    {
        $payload = \Resque::pop($queue);
        if (!is_array($payload))
        {
            return false;
        }

        return new ResqueJob($queue, $payload, $sm);
    }

    /**
     * Get the instantiated object for this job that will be performing work.
     *
     * @return object Instance of the object that this job belongs to.
     */
    public function getInstance()
    {
        if (!is_null($this->instance))
        {
            return $this->instance;
        }


        try
        {
            $this->instance = $this
                    ->getServiceManager()
                    ->get($this->payload['class']);
            $this->instance->job = $this;
            $this->instance->args = $this->getArguments();
            $this->instance->queue = $this->queue;
        } catch (\Exception $e)
        {
            throw new \Resque_Exception($e->getMessage());
        }

        return $this->instance;
    }
}
