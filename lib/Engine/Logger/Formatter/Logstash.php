<?php
/**
 * @namespace
 */
namespace Engine\Logger\Formatter;

use \Phalcon\Logger as Logger,
    \Phalcon\Logger\FormatterInterface;

/**
 * Class Logstash
 *
 * @category    Engine
 * @package     Logger
 * @subpackage  Formatter
 */
class Logstash implements FormatterInterface
{
    const V0 = 0;
    const V1 = 1;

    protected $_dateFormat;

    /**
     * @var string the name of the system for the Logstash log message, used to fill the @source field
     */
    protected $_systemName;

    /**
     * @var string an application name for the Logstash log message, used to fill the @type field
     */
    protected $_applicationName;

    /**
     * @var string an channel name for the Logstash log message, used to fill the @type field
     */
    protected $__channelName;

    /**
     * @var string a prefix for 'extra' fields from the Monolog record (optional)
     */
    protected $_extraPrefix;

    /**
     * @var string a prefix for 'context' fields from the Monolog record (optional)
     */
    protected $contextPrefix;

    /**
     * @var int logstash format version to use
     */
    protected $_version;

    /**
     * @param string $applicationName the application that sends the data, used as the "type" field of logstash
     * @param string $channel the logging channel
     * @param string $systemName      the system/machine name, used as the "source" field of logstash, defaults to the hostname of the machine
     * @param string $extraPrefix     prefix for extra keys inside logstash "fields"
     * @param string $contextPrefix   prefix for context keys inside logstash "fields", defaults to ctxt_
     * @param int    $version         the logstash format version to use, defaults to 0
     */
    public function __construct($applicationName, $channel = null, $systemName = null, $extraPrefix = null, $contextPrefix = 'ctxt_', $version = self::V0)
    {
        $this->_dateFormat = 'Y-m-d\TH:i:s.uP';
        if (!function_exists('json_encode')) {
            throw new \RuntimeException('PHP\'s json extension is required to use Monolog\'s NormalizerFormatter');
        }

        $this->_systemName = $systemName ?: gethostname();
        $this->_applicationName = $applicationName;
        $this->_channelName = $channel;
        $this->_extraPrefix = $extraPrefix;
        $this->_contextPrefix = $contextPrefix;
        $this->_version = $version;
    }

    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param array|string $message
     * @param int $type
     * @param int $timestamp
     * @param null $context
     * @return array|mixed|string
     */
    public function format($message, $type, $timestamp, $context = NULL)
    {
        $record = [];
        $record['message'] = $message;
        $record['datetime'] = gmdate('c', $timestamp);
        $record['level'] = $this->getTypeString($type);
        if (!empty($context)) {
            $record['context'] = $context;
        }

        $record = $this->_normalize($record);

        if ($this->_version === self::V1) {
            $message = $this->_formatV1($record);
        } else {
            $message = $this->_formatV0($record);
        }

        return $this->_toJson($message) . "\n";
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function _formatV0(array $record)
    {
        if (empty($record['datetime'])) {
            $record['datetime'] = gmdate('c');
        }
        $message = [
            '@timestamp' => $record['datetime'],
            '@source' => $this->_systemName,
            '@fields' => [],
        ];
        if (isset($record['message'])) {
            $message['@message'] = $record['message'];
        }
        if (isset($record['channel'])) {
            $message['@tags'] = [$record['channel']];
            $message['@fields']['channel'] = $record['channel'];
        } elseif ($this->_channelName) {
            $message['@tags'] = [$this->_channelName];
            $message['@fields']['channel'] = $this->_channelName;
        }
        if (isset($record['level'])) {
            $message['@fields']['level'] = $record['level'];
        }
        if ($this->_applicationName) {
            $message['@type'] = $this->_applicationName;
        }
        if (isset($record['extra']['server'])) {
            $message['@source_host'] = $record['extra']['server'];
        }
        if (isset($record['extra']['url'])) {
            $message['@source_path'] = $record['extra']['url'];
        }
        if (!empty($record['extra'])) {
            foreach ($record['extra'] as $key => $val) {
                $message['@fields'][$this->_extraPrefix . $key] = $val;
            }
        }
        if (!empty($record['context'])) {
            foreach ($record['context'] as $key => $val) {
                $message['@fields'][$this->_contextPrefix . $key] = $val;
            }
        }

        return $message;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function _formatV1(array $record)
    {
        if (empty($record['datetime'])) {
            $record['datetime'] = gmdate('c');
        }
        $message = [
            '@timestamp' => $record['datetime'],
            '@version' => 1,
            'host' => $this->_systemName,
        ];
        if (isset($record['message'])) {
            $message['message'] = $record['message'];
        }
        if (isset($record['channel'])) {
            $message['type'] = $record['channel'];
            $message['channel'] = $record['channel'];
        } elseif ($this->_channelName) {
            $message['type'] = $this->_channelName;
            $message['channel'] = $this->_channelName;
        }
        if (isset($record['level_name'])) {
            $message['level'] = $record['level_name'];
        }
        if ($this->_applicationName) {
            $message['type'] = $this->_applicationName;
        }
        if (!empty($record['extra'])) {
            foreach ($record['extra'] as $key => $val) {
                $message[$this->_extraPrefix . $key] = $val;
            }
        }
        if (!empty($record['context'])) {
            foreach ($record['context'] as $key => $val) {
                $message[$this->_contextPrefix . $key] = $val;
            }
        }

        return $message;
    }

    /**
     * Returns the string meaning of a logger constant
     *
     * @return string
     */
    public function getTypeString($type)
    {
        switch ($type) {
            case Logger::DEBUG:
                return "DEBUG";

            case Logger::ERROR:
                return "ERROR";

            case Logger::WARNING:
                return "WARNING";

            case Logger::CRITICAL:
                return "CRITICAL";

            case Logger::CUSTOM:
                return "CUSTOM";

            case Logger::ALERT:
                return "ALERT";

            case Logger::NOTICE:
                return "NOTICE";

            case Logger::INFO:
                return "INFO";

            case Logger::EMERGENCY:
                return "EMERGENCY";

            case Logger::SPECIAL:
                return "SPECIAL";
        }

        return "CUSTOM";
    }

    /**
     * Normalize data
     *
     * @param mixed $data
     *
     * @return array|string
     */
    protected function _normalize($data)
    {
        if (null === $data || is_scalar($data)) {
            if (is_float($data)) {
                if (is_infinite($data)) {
                    return ($data > 0 ? '' : '-') . 'INF';
                }
                if (is_nan($data)) {
                    return 'NaN';
                }
            }

            return $data;
        }

        if (is_array($data) || $data instanceof \Traversable) {
            $normalized = array();

            $count = 1;
            foreach ($data as $key => $value) {
                if ($count++ >= 1000) {
                    $normalized['...'] = 'Over 1000 items, aborting normalization';
                    break;
                }
                $normalized[$key] = $this->_normalize($value);
            }

            return $normalized;
        }

        if ($data instanceof \DateTime) {
            return $data->format($this->_dateFormat);
        }

        if (is_object($data)) {
            // TODO 2.0 only check for Throwable
            if ($data instanceof \Engine\Exception || (PHP_VERSION_ID > 70000 && $data instanceof \Throwable)) {
                return $this->_normalizeException($data);
            }

            // non-serializable objects that implement __toString stringified
            if (method_exists($data, '__toString') && !$data instanceof \JsonSerializable) {
                $value = $data->__toString();
            } else {
                // the rest is json-serialized in some way
                $value = $this->_toJson($data, true);
            }

            return sprintf("[object] (%s: %s)", get_class($data), $value);
        }

        if (is_resource($data)) {
            return sprintf('[resource] (%s)', get_resource_type($data));
        }

        return '[unknown('.gettype($data).')]';
    }

    /**
     * Normalize exception
     *
     * @param $e
     *
     * @return array
     */
    protected function _normalizeException($e)
    {
        // TODO 2.0 only check for Throwable
        if (!$e instanceof \Exception && !$e instanceof \Throwable) {
            throw new \InvalidArgumentException('Exception/Throwable expected, got '.gettype($e).' / '.get_class($e));
        }

        $data = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile().':'.$e->getLine(),
        ];

        $trace = $e->getTrace();
        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $data['trace'][] = $frame['file'].':'.$frame['line'];
            } else {
                // We should again normalize the frames, because it might contain invalid items
                $data['trace'][] = $this->_toJson($this->_normalize($frame), true);
            }
        }

        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->_normalizeException($previous);
        }

        return $data;
    }

    /**
     * Return the JSON representation of a value
     *
     * @param  mixed             $data
     * @param  bool              $ignoreErrors
     * @throws \RuntimeException if encoding fails and errors are not ignored
     *
     * @return string
     */
    protected function _toJson($data, $ignoreErrors = false)
    {
        // suppress json_encode errors since it's twitchy with some inputs
        if ($ignoreErrors) {
            return @$this->_jsonEncode($data);
        }

        $json = $this->_jsonEncode($data);

        if ($json === false) {
            $json = $this->_handleJsonError(json_last_error(), $data);
        }

        return $json;
    }

    /**
     * @param  mixed  $data
     *
     * @return string JSON encoded data or null on failure
     */
    private function _jsonEncode($data)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return json_encode($data);
    }

    /**
     * Handle a json_encode failure.
     *
     * If the failure is due to invalid string encoding, try to clean the
     * input and encode again. If the second encoding iattempt fails, the
     * inital error is not encoding related or the input can't be cleaned then
     * raise a descriptive exception.
     *
     * @param  int               $code return code of json_last_error function
     * @param  mixed             $data data that was meant to be encoded
     * @throws \RuntimeException if failure can't be corrected
     *
     * @return string            JSON encoded data after error correction
     */
    private function _handleJsonError($code, $data)
    {
        if ($code !== JSON_ERROR_UTF8) {
            $this->_throwEncodeError($code, $data);
        }

        if (is_string($data)) {
            $this->detectAndCleanUtf8($data);
        } elseif (is_array($data)) {
            array_walk_recursive($data, array($this, 'detectAndCleanUtf8'));
        } else {
            $this->_throwEncodeError($code, $data);
        }

        $json = $this->_jsonEncode($data);

        if ($json === false) {
            $this->_throwEncodeError(json_last_error(), $data);
        }

        return $json;
    }

    /**
     * Throws an exception according to a given code with a customized message
     *
     * @param  int               $code return code of json_last_error function
     * @param  mixed             $data data that was meant to be encoded
     * @throws \RuntimeException
     */
    private function _throwEncodeError($code, $data)
    {
        switch ($code) {
            case JSON_ERROR_DEPTH:
                $msg = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Unexpected control character found';
                break;
            case JSON_ERROR_UTF8:
                $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $msg = 'Unknown error';
        }

        throw new \RuntimeException('JSON encoding failed: '.$msg.'. Encoding: '.var_export($data, true));
    }

    /**
     * Detect invalid UTF-8 string characters and convert to valid UTF-8.
     *
     * Valid UTF-8 input will be left unmodified, but strings containing
     * invalid UTF-8 codepoints will be reencoded as UTF-8 with an assumed
     * original encoding of ISO-8859-15. This conversion may result in
     * incorrect output if the actual encoding was not ISO-8859-15, but it
     * will be clean UTF-8 output and will not rely on expensive and fragile
     * detection algorithms.
     *
     * Function converts the input in place in the passed variable so that it
     * can be used as a callback for array_walk_recursive.
     *
     * @param mixed &$data Input to check and convert if needed
     * @private
     */
    public function detectAndCleanUtf8(&$data)
    {
        if (is_string($data) && !preg_match('//u', $data)) {
            $data = preg_replace_callback(
                '/[\x80-\xFF]+/',
                function ($m) { return utf8_encode($m[0]); },
                $data
            );
            $data = str_replace(
                array('¤', '¦', '¨', '´', '¸', '¼', '½', '¾'),
                array('€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'),
                $data
            );
        }
    }
}