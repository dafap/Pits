<?php
/**
 * Validateur de date et de time
 *
 * Utilise le modele de dates décrit en application/models
 *
 * @project pits
 * @package application/forms/validate
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 10 juin 2011
 * @version $Id: $
 */

class Pits_Validate_Date extends Zend_Validate_Abstract
{
    const INVALID        = 'dateInvalid';
    const INVALID_DATE   = 'dateInvalidDate';
    const INVALID_TIME   = 'dateInvalidTime';
    const EXCEPTION_DATE   = 'dateException';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
    self::INVALID        => "Type de donnée invalide.",
    self::INVALID_DATE   => "'%value%' n'est pas une date valide.",
    self::INVALID_TIME    => "'%value%' n'est pas un horaire valide.",
    self::EXCEPTION_DATE => "Erreur de date : '%errmsg%'"
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'errmsg'  => 'errPitsModelDate',
    );
    
    public $errPitsModelDate;

    private $_locale;
    private $_sepdate;
    private $_septime;
    private $_time;

    /**
     * Initialisé :
     * - soit par un Zend_Config
     * - soit par les paramères 'locale', 'sepdate', 'septime', 'time' (tous optionnels mais dans cet ordre)
     * - soit par un tableau array('locale' => 'fr_FR'|'', 'sepdate' => char, 'septime' => char, 'time' => boolean)
     *
     * Par défaut, si aucun paramètre n'est défini, la locale sera fr_FR, sepdate='/', septime=':', time=false
     * (time=true si ce n'est qu'un horaire sans la partie date)
     *
     * @param Zend_Config|array|strings $options
     */
    public function __construct($options = array())
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } elseif (!is_array($options)) {
            $options = func_get_args();
            $temp['locale'] = array_shift($options);
            if (!empty($options)) {
                $temp['sepdate'] = array_shift($options);
                if (!empty($options)) {
                    $temp['septime'] = array_shift($options);
                    if (!empty($options)) {
                        $temp['time'] = array_shift($options);
                    }

                }
            }
            $options = $temp;
        }
        if (!array_key_exists('locale', $options)) {
            $options['locale'] = 'fr_FR';
        }
        if (!array_key_exists('sepdate', $options)) {
            $options['sepdate'] = '/';
        }
        if (!array_key_exists('septime', $options)) {
            $options['septime'] = ':';
        }
        if (!array_key_exists('time', $options)) {
            $options['time'] = false;
        }
        $this->setLocale($options['locale'])
        ->setSepdate($options['sepdate'])->setSeptime($options['septime'])->setTime($options['time']);
        //if ($this->getTime()) {Zend_Debug::dump($this);exit;}
    }
    /**
     * (non-PHPdoc)
     * @see Zend_Validate_Interface::isValid()
     */
    public function isValid($value)
    {
        $result = true;
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            $result = false;
        } else {
            $this->_setValue($value);
            try {
                $ladate = new Pits_Model_Date($value, $this->_locale, $this->_sepdate, $this->_septime, $this->_time);
                if ($this->_time) {
                    if (!$ladate->isTime()) {
                        $this->_error(self::INVALID_TIME);
                        $result = false;
                    }
                } else {
                    if (!$ladate->isDate()) {
                        $this->_error(self::INVALID_DATE);
                        $result = false;
                    } elseif (!$ladate->isTime()) {
                        $this->_error(self::INVALID_TIME);
                        $result = false;
                    }
                }
            } catch (Pits_Model_Exception $e) {
                $this->errPitsModelDate = $e->getMessage();
                $this->_error(self::EXCEPTION_DATE);
                $result = false;
            }
        }
        return $result;
    }
    /**
     * Locale : 'fr_FR' ou '' (US)
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->_locale = $locale;
        return $this;
    }
    /**
     * Séparateur de date
     * @param string(1) $sepdate
     */
    public function setSepdate($sepdate)
    {
        $this->_sepdate = $sepdate;
        return $this;
    }
    /**
     * Séparateur de time
     * @param string(1) $septime
     */
    public function setSeptime($septime)
    {
        $this->_septime = $septime;
        return $this;
    }
    /**
     * Indique si ce n'est qu'un horaire sans partie date
     * @param boolean $time
     */
    public function setTime($time)
    {
        $this->_time = (bool) $time;
        return $this;
    }

    public function getLocale()
    {
        return $this->_locale;
    }
    public function getSepdate()
    {
        return $this->_sepdate;
    }
    public function getSeptime()
    {
        return $this->_septime;
    }
    public function getTime()
    {
        return $this->_time;
    }
}