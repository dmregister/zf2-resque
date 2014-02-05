<?php

namespace Zf2Resque\Service;

use Zend\ServiceManager\ServiceManager;
use Zf2Resque\Service\ResqueJob;
use Zend\Mail;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

class ResqueWorker extends \Resque_Worker
{

    protected $serviceManager;

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
     * Given a worker ID, find it and return an instantiated worker class for it.
     *
     * @param string $workerId The ID of the worker.
     * @return Resque_Worker Instance of the worker. False if the worker does not exist.
     */
    public static function find($workerId)
    {
        if (!self::exists($workerId) || false === strpos($workerId, ":"))
        {
            return false;
        }

        list($hostname, $pid, $queues) = explode(':', $workerId, 3);
        $queues = explode(',', $queues);
        $worker = new self($queues, self::getServiceManager());
        $worker->setId($workerId);
        return $worker;
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
    public function workingOn(ResqueJob $job)
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
        
        $config = $this->getServiceManager()->get('config');
        
        if($config['zf2resqueue']['emailOnShutDown'] === true)
        {
            $emailSettings = $config['zf2resqueue']['emailNotification'];
            
            // Initialize parameters
            $htmlBody = 'The worker queue has stopped.';
            $textBody = 'The worker queue has stopped.';

            $subject = 'Please restart the worker';
            $to = $emailSettings['to'];
            $toName = $emailSettings['toName'];
            $from = $emailSettings['from'];
            $fromName = $emailSettings['fromName'];

            // Setup SMTP transport using LOGIN authentication
            $transport = new SmtpTransport();
            $smtpOptions = $config['smtp_transport']['smtp_options'];
            $options = new SmtpOptions($smtpOptions);
            $transport->setOptions($options);

            $htmlPart = new MimePart($htmlBody);
            $htmlPart->type = "text/html";

            $textPart = new MimePart($textBody);
            $textPart->type = "text/plain";

            $body = new MimeMessage();
            $body->setParts(array($textPart, $htmlPart));

            $message = new Mail\Message();
            $message->setBody($body);
            $message->setFrom($from, $fromName);
            $message->addTo($to, $toName);
            $message->setSubject($subject);
            $message->setEncoding("UTF-8");
            $message->getHeaders()
                    ->get('content-type')
                    ->setType('multipart/alternative');

            $transport->send($message);
        }
    }
}