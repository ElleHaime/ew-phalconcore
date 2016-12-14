<?php
/**
 * @namespace
 */
namespace Engine\Logger;

use \Monolog\Logger as MLogger,
    \Monolog\Handler\StreamHandler as MStreamHandler,
    \Monolog\Handler\HandlerInterface as MHandlerInterface,

    \Phalcon\Logger\AdapterInterface as PAdapterInterface,
    \Phalcon\Logger as PLogger,
    \Phalcon\Logger\Adapter as PAdapter;

/**
 * Class Monolog
 *
 * @category    Engine
 * @package     Logger
 */
class Monolog extends PAdapter implements PAdapterInterface
{
    /**
     * Monolog adapter object
     * @var \Monolog\Logger
     */
    protected $_monolog;

    /**
     * Log levels
     * @var array
     */
    private $_levelMapping = [
        PLogger::DEBUG    => MLogger::DEBUG,
        PLogger::INFO     => MLogger::INFO,
        PLogger::NOTICE   => MLogger::NOTICE,
        PLogger::WARNING  => MLogger::WARNING,
        PLogger::ERROR    => MLogger::ERROR,
        PLogger::ALERT    => MLogger::ALERT,
        PLogger::EMERGENCY=> MLogger::EMERGENCY
    ];

    /**
     * Logger constructor
     *
     * @param string             $name       The logging channel
     * @param MHandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
     * @param callable[]         $processors Optional array of processors
     */
    public function __construct($name, array $handlers = [], array $processors = [])
    {
        $this->_monolog = new MLogger($name, $handlers, $processors);
    }

    /**
     * Adds a log record.
     *
     * @param int|mixed|string $type
     * @param null $message
     * @param array $context
     * @return Boolean Whether the record has been processed
     */
    public function log($type, $message = NULL, array $context = NULL)
    {
        if ($message === null) {
            $message = $type;
            $type = PLogger::INFO;
        }
        return $this->_monolog->addRecord($this->_levelMapping[$type], $message, $context);
    }

    /**
     * Return formatter
     *
     * @return \Monolog\Formatter\FormatterInterface
     */
    public function getFormatter()
    {
        $handler = $this->getFormatter();

        return ($handler) ? $handler->getFormatter() : false;
    }

    public function close()
    {
    }

    /**
     * Set a handler on to the stack.
     */
    public function setHandler($handler)
    {
        $this->_monolog->setHandlers([$handler]);
    }

    /**
     * Return handler
     */
    public function getHandler()
    {
        $handlers = $this->_monolog->getHandlers();
        if (empty($handlers)) {
            return false;
        }

        return $handlers[0];
    }

    /**
     * Call monolog methods
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \Engine\Exception
     */
    public function __call(string $name , array $arguments)
    {
        $callable = [$this->_monolog, $name];
        if (!is_callable($callable)) {
            throw new \Engine\Exception('The first argument to call() must be a valid callable.');
        }

        try {
            return call_user_func_array($callable, $arguments);
        }
        catch (\Exception $e) {
            //ob_end_clean();
            throw new \Engine\Exception($e->getMessage(), $e->getCode());
        }
    }
}