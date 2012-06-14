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
class Pits_Validate_ChoixSelect extends Zend_Validate_Abstract
{
    const INVALID      = 'choixSelectInvalid';
    const PERSONNALISE = 'choixSelectMessage';
    protected $_messageTemplates = array(
    self::INVALID => 'Vous devez choisir une valeur dans la liste',
    self::PERSONNALISE => '',       
    );
    private $_sanschoix;
    private $_errorMsg;

    /**
     * Place la valeur interdite correspondant à la ligne 1 --- Choisissez ...
     * @param unknown_type $sanschoix
     */
    public function __construct($options = array())
    {
        $this->_errorMsg = self::INVALID;
        if (is_array($options)) {
            if (array_key_exists('sanschoix', $options)) {
                $this->_sanschoix = $options['sanschoix'];
            } else {
                $this->_sanschoix = '?';
            }
            if (array_key_exists('message', $options)) {
                $this->_errorMsg = self::PERSONNALISE;
                $this->_messageTemplates[self::PERSONNALISE] = $options['message'];
            }
        } else {
            $this->_sanschoix = empty($options) ? '?' : $options;
        }
    }
    public function isValid($value)
    {
        if ($value == $this->_sanschoix) {
            $this->_error($this->_errorMsg);
            return false;
        }
        return true;
    }
}