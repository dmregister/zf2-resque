<?php

namespace Zf2Resque\Service;

/**
 * Resque default logger PSR-3 compliant
 *
 * @package		Resque/Stat
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class ResqueLog extends \Resque_Log
{

    public $verbose;
    public $filePath;

    public function __construct($verbose = false, $filePath = '')
    {
        $this->verbose = $verbose;

        $this->filePath = $filePath;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level    PSR-3 log level constant, or equivalent string
     * @param string  $message  Message to log, may contain a { placeholder }
     * @param array   $context  Variables to replace { placeholder }
     * @return null
     */
    public function log($level, $message, array $context = array())
    {

        if ($this->verbose)
        {
            if (!file_exists($this->filePath))
            {
                parent::log($level, $message, $context);
                return;
            }

            file_put_contents($this->filePath,
                    '[' . $level . '] [' . strftime('%T %Y-%m-%d') . '] ' . $this->interpolate($message,
                            $context) . PHP_EOL, FILE_APPEND);

            return;
        }

        if (!($level === \Psr\Log\LogLevel::INFO || $level === \Psr\Log\LogLevel::DEBUG))
        {
            if (!file_exists($this->filePath))
            {
                parent::log($level, $message, $context);
                return;
            }

            file_put_contents($this->filePath,
                    '[' . $level . '] ' . $this->interpolate($message, $context) . PHP_EOL,
                    FILE_APPEND);
            
            return;
        }
    }

}