<?php
/**
 * ErrorHandler derives from yii\web\ErrorHandler but can be customized
 * in terms of which error types to handle.
 *
 * @author cronfy <cronfy@gmail.com>
 */

namespace cronfy\yii\web;

use yii\base\ErrorException;
use yii\web\ErrorHandler as BaseErrorHandler;

class ErrorHandler extends BaseErrorHandler {

    /**
     * @var int php error types that will be handled by ErrorHandler.
     * It is posible configuration when particular error type is discarded:
     * i is handled, but not sent to log or conerted to exception.
     * */
    public $typesToHandle = E_ALL | E_STRICT;

    /**
     * @var int php error types that will be forwarded to log
     * */
    public $typesToLog = E_ALL | E_STRICT;

    /**
     * @var int php error types that will be converted to exceptions
     * */
    public $typesToExceptions = E_ALL | E_STRICT;

    /**
     * @var bool whether to catch fatal errors with ErrorHandler
     */
    public $catchFatals = true;

    /**
     * @var boolean Used to override display_errors php ini setting
     */
    public $display_errors;

    /**
     * @var string Used to reserve memory for fatal error handler.
     */
    private $_memoryReserve;
    /**
     * @var \Exception from HHVM error that stores backtrace
     */
    private $_hhvmException;

    /**
     * Register this error handler
     */
    public function register()
    {
        if (!is_null($this->display_errors)) ini_set('display_errors', $this->display_errors);

        set_exception_handler([$this, 'handleException']);
        if (defined('HHVM_VERSION')) {
            set_error_handler([$this, 'handleHhvmError']);
        } else {
            set_error_handler([$this, 'handleError']);
        }
        if ($this->memoryReserveSize > 0) {
            $this->_memoryReserve = str_repeat('x', $this->memoryReserveSize);
        }
        if ($this->catchFatals) {
            register_shutdown_function([$this, 'handleFatalError']);
        }
    }

    public function handleError($code, $message, $file, $line)
    {
        if (!(error_reporting() & $code)) {
            return false; // to internal php handler
        }

        if (!($code & $this->typesToHandle)) {
            return false; // to internal php handler
        }

        if (!($code & ($this->typesToExceptions | $this->typesToLog))) {
            return true; // skip internal php handler
        }

        // load ErrorException manually here because autoloading them will not work
        // when error occurs while autoloading a class
        if (!class_exists('yii\\base\\ErrorException', false)) {
            // find location of yii\\base\\ErrorException
            $basedir = dirname((new \ReflectionObject($this))->getParentClass()->getParentClass()->getFileName());
            require_once($basedir . '/ErrorException.php');
        }
        $exception = new ErrorException($message, $code, $code, $file, $line);

        if ($code & $this->typesToExceptions) {
            // in case error appeared in __toString method we can't throw any exception
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);
                    if (defined('HHVM_VERSION')) {
                        flush();
                    }
                    exit(1);
                }
            }

            throw $exception;
        }

        if ($code & $this->typesToLog) {
            $this->logException($exception);
        }

        return true; // skip internal php handler
    }

}