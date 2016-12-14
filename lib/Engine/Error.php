<?php
/**
 * @namespace
 */
namespace Engine;

use \Phalcon\DI\FactoryDefault as Di,
    \Phalcon\Exception as PhException;

/**
 * Class Exception
 *
 * @category   Engine
 * @package    Error
 */
class Error
{
    /**
     * Log normal error
     *
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     */
    public static function normal($type, $message, $file, $line)
    {
        // Log it
        self::logError(
            $type,
            $message,
            $file,
            $line
        );

        // Display it under regular circumstances
    }

    /**
     * Trigger error_get_last
     */
    public static function shutdown()
    {
        $error = error_get_last();
        if (!$error)
            return;

        // Log it
        self::logError(
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );

        // Display it under regular circumstances
    }

    /**
     * Log exception
     *
     * @param \Exception $exception
     *
     * @return void
     */
    public static function exception(\Exception $exception)
    {
        // Log the error
        self::logError(
            'Exception',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        //Display it
        $dependencyInjector = Di::getDefault();
        if ($dependencyInjector->has('debug')) {
            $debug = $dependencyInjector->get('debug');
            if ($debug) {
                throw $exception;
            }
        }
    }

    /**
     * Write error to log
     *
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @param string $trace
     * @throws \Phalcon\Exception
     */
    protected static function logError($type, $message, $file, $line, $trace = '')
    {
        $dependencyInjector = Di::getDefault();
        $template = "[%s] %s (File: %s Line: [%s])";
        $logMessage = sprintf($template, $type, $message, $file, $line);

        if ($dependencyInjector->has('profiler')) {
            $profiler = $dependencyInjector->get('profiler');
            if ($profiler) {
                $profiler->addError($logMessage, $trace);
            }
        }

        if ($trace) {
            $logMessage .= $trace . PHP_EOL;
        } else {
            $logMessage .= PHP_EOL;
        }

        if ($dependencyInjector->has('logger')) {
            $logger = $dependencyInjector->get('logger');
            if ($logger) {
                $logger->error($logMessage);
            }
        }
    }
}