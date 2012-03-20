<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 18 mai 2010
 * 
 * @category   pits
 * @package    livrary
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

require_once 'Zend/Validate/Abstract.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Validate_NbDigits extends Zend_Validate_Abstract
{
    const INVALID      = 'nbDigitsInvalid';
    public $minimum;
    protected $_messageTemplates = array(self::INVALID => "Votre mot de passe doit comporter au moins '%min%' chiffres.");
    protected $_messageVariables = array('min' => 'minimum');
    
    /**
     * Place le nombre minimum de chiffres imposés
     * @param unknown_type $sanschoix
     */
    public function __construct($nbDigits = 1)
    {
        $this->minimum = $nbDigits;
    }
    /**
     * @param chaine $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (preg_match_all("#\d#",$value,$result) < $this->minimum) {
            $this->_error(self::INVALID);
            return false;
        }
        return true;
    }
}