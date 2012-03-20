<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 3 août 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits/validate
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits/validate
 * @author     pomirol
 */
class Pits_Validate_Date extends Zend_Validate_Abstract
{
    const INVALID_DATE = 'dateInvalidDate';
    const INVALID_TYPE = 'dateInvalidType';
    const FALSE_FORMAT = 'dateFalseFormat';
    /**
     * Messages prévus en cas d'échec de validation
     * @var array of string
     */
    protected $_messageTemplates = array(
    //self::INVALID_TYPE   => "Type invalide donné, la valeur devrait être de type `string`, `integer`, `array` ou `Pits_Date`",
    //self::INVALID_DATE   => "'%value%' ne semble pas être une date valide",
    //self::FALSE_FORMAT   => "exception message",
    self::INVALID_DATE =>'%message%',
    );
    protected $_messageVariables = array(
    'message' => '_errorMessage'
    );
    /**
     * Variables privées du validateur
     */
    private $_locale;
    private $_sepdate;
    private $_septime;
    protected $_errorMessage;

    /**
     * Constructeur qui met en place la locale et les séparateurs de date et de time
     *
     * @param string $locale
     * @param string $sepdate
     * @param string $septime
     */
    public function __construct($locale='fr_FR', $sepdate='/', $septime=':')
    {
        $this->_locale = $locale;
        $this->_sepdate = $sepdate;
        $this->_septime = $septime;
    }

    public function isValid($value)
    {
        //Zend_Debug::dump($value); exit;

        $this->_setValue((string) $value);
        try {
            $d = new Pits_Date($value, $this->_locale, $this->_sepdate, $this->_septime);
            //Zend_Debug::dump($d); exit;
            if ($d->isError()) {
                $this->_errorMessage = $d->getErrorMessage();
                $this->_error(self::INVALID_DATE);
                //Zend_Debug::dump(array('false', $this->_messageTemplates)); exit;
                return false;
            }
            //Zend_Debug::dump(array('true', $this->messageTemplates)); exit;
            return true;
        } catch (Pits_Date_Exception $e) {
            $this->_errorMessage = $e->getMessage();
            $this->_error(self::INVALID_DATE);
            return false;
        }
    }
}