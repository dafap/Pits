<?php
/**
 * @package package.exceptions.errors
 * @author Johan Barbier <barbier_johan@hotmail.com>
 * @version 20071017
 * @desc package transforming all php errors into catchable exceptions
 *
 */

/**
 * @name exceptionErrorGeneric
 * @desc Generic exceptionError class. Overload Exception::__toString() method and Exception::__construct() method
 *
 */
abstract class exceptionErrorGeneric extends Exception {
    /**
     * @desc Error type
     *
     * @var string
     */
    protected $sType;

    /**
     * @desc Error file
     *
     * @var string
     */
    protected $sErrFile;

    /**
     * @desc Error line
     *
     * @var int
     */
    protected $iErrLine;

    /**
     * @desc Error context
     *
     * @var mixed
     */
    protected $mVars;

    /**
     * @desc is context enabled or not
     *
     * @var boolean
     */
    protected $bContext;

    /**
     * @desc constructor
     *
     * @param constant $cErrno
     * @param string $sErrStr
     * @param string $sErrFile
     * @param int $iErrLine
     * @param mixed $mVars
     * @param boolean $bContext
     */
    public function __construct($cErrno, $sErrStr, $sErrFile, $iErrLine, $mVars, $bContext = false) {
        parent::__construct($sErrStr, $cErrno);
        $this->sErrFile = $sErrFile;
        $this->iErrLine = $iErrLine;
        $this->mVars = $mVars;
        $this->bContext = $bContext;
    }

    /**
     * @desc Exception::__toString() overloading
     *
     * @return string
     */
    public function dump() {
        $sMsg = '<pre>';
        $sMsg .= '<strong>'.$this->sType.'</strong><br />';
        $sMsg .= $this->getMessage().'['.$this->getCode().']<br />';
        $sMsg .= '<em>File</em> : '.$this->sErrFile.' on line '.$this->iErrLine.'<br />';
        $sMsg .= '<em>Trace</em> :<br />'.$this->getTraceAsString().'<br />';
        if(true === $this->bContext) {
            $sMsg .= '<em>Context</em> :<br />'.print_r($this->mVars, true);
        }
        $sMsg .= '</pre>';
        return $sMsg;
    }
    public function __toString() {
        $sMsg = PHP_EOL . $this->sType . PHP_EOL;
        $sMsg .= $this->getMessage() . '['. $this->getCode() . ']' . PHP_EOL;
        $sMsg .= 'File : ' . $this->sErrFile . ' on line ' . $this->iErrLine . PHP_EOL;
        $sMsg .= 'Trace :' . PHP_EOL . $this->getTraceAsString() . PHP_EOL;
        if(true === $this->bContext) {
            $sMsg .= 'Context :' . PHP_EOL . print_r($this->mVars, true) . PHP_EOL;
        }
        return $sMsg;
    }
}

/**
 * @desc exceptionErrors for Fatal errors
 *
 */
class exceptionErrorError extends exceptionErrorGeneric {
    protected $sType = 'Fatal error';
}

/**
 * @desc exceptionErrors for Warnings
 *
 */
class exceptionErrorWarning extends exceptionErrorGeneric {
    protected $sType = 'Warning';
}

/**
 * @desc exceptionErrors for Parse errors
 *
 */
class exceptionErrorParseError extends exceptionErrorGeneric {
    protected $sType = 'Parse error';
}

/**
 * @desc exceptionErrors for Notice
 *
 */
class exceptionErrorNotice extends exceptionErrorGeneric {
    protected $sType = 'Notice';
}

/**
 * @desc exceptionErrors for Core errors
 *
 */
class exceptionErrorCoreError extends exceptionErrorGeneric {
    protected $sType = 'Core error';
}

/**
 * @desc exceptionErrors for Core warnings
 *
 */
class exceptionErrorCoreWarning extends exceptionErrorGeneric {
    protected $sType = 'Core warning';
}

/**
 * @desc exceptionErrors for Compile errors
 *
 */
class exceptionErrorCompileError extends exceptionErrorGeneric {
    protected $sType = 'Compile error';
}

/**
 * @desc exceptionErrors for Compile warnings
 *
 */
class exceptionErrorCompileWarning extends exceptionErrorGeneric {
    protected $sType = 'Compile warning';
}

/**
 * @desc exceptionErrors for User errors
 *
 */
class exceptionErrorUserError extends exceptionErrorGeneric {
    protected $sType = 'User error';
}

/**
 * @desc exceptionErrors for User warnings
 *
 */
class exceptionErrorUserWarning extends exceptionErrorGeneric {
    protected $sType = 'User warning';
}

/**
 * @desc exceptionErrors for User notices
 *
 */
class exceptionErrorUserNotice extends exceptionErrorGeneric {
    protected $sType = 'User notice';
}

/**
 * @desc exceptionErrors for Strict errors
 *
 */
class exceptionErrorStrictError extends exceptionErrorGeneric {
    protected $sType = 'Strict error';
}

/**
 * @desc exceptionErrors for not handled yet errors
 *
 */
class exceptionErrorNotHandledYet extends exceptionErrorGeneric {
    protected $sType = 'Not handled yet';
}

/**
 * @desc error handler, calling correct exceptionError type
 *
 */
class exceptionErrorHandler {
    /**
     * @desc translation between context error and exceptionError type of class
     *
     * @var array
     */
    public static $aTrans = array (
    E_ERROR => 'exceptionErrorError',
    E_WARNING => 'exceptionErrorWarning',
    E_PARSE => 'exceptionErrorParseError',
    E_NOTICE => 'exceptionErrorNotice',
    E_CORE_ERROR => 'exceptionErrorCoreError',
    E_CORE_WARNING => 'exceptionErrorCoreWarning',
    E_COMPILE_ERROR => 'exceptionErrorCompileError',
    E_COMPILE_WARNING => 'exceptionErrorCompileWarning',
    E_USER_ERROR => 'exceptionErrorUserError',
    E_USER_WARNING => 'exceptionErrorUserWarning',
    E_USER_NOTICE => 'exceptionErrorUserNotice',
    E_STRICT => 'exceptionErrorStrictError'
    );

    /**
     * @desc is context enabled or not
     *
     * @var boolean
     */
    public static $bContext = false;

    /**
     * @desc constructor, optional bContext boolean can be given if you want context to be displayed or not
     *
     * @param boolean $bContext (optional, default = false)
     */
    public function __construct($bContext = false) {
        self::$bContext = $bContext;
        set_error_handler(array ($this, 'errorHandler'));
    }

    /**
     * @desc error handler
     *
     * @param constant $cErrno
     * @param string $sErrStr
     * @param string $sErrFile
     * @param int $iErrLine
     * @param mixed $mVars
     */
    public function errorHandler ($cErrno, $sErrStr, $sErrFile, $iErrLine, $mVars) {
        if(!isset(self::$aTrans[$cErrno])) {
            throw new exceptionErrorNotHandledYet($cErrno, $sErrStr, $sErrFile, $iErrLine, $mVars, self::$bContext);
        } else {
            throw new self::$aTrans[$cErrno]($cErrno, $sErrStr, $sErrFile, $iErrLine, $mVars, self::$bContext);
        }
    }
}
