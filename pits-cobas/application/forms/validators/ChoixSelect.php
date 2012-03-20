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
    protected $_messageTemplates = array(
    self::INVALID => 'Vous devez choisir une valeur dans la liste'
    );
    private $_sanschoix;

    /**
     * Place la valeur interdite correspondant à la ligne 1 --- Choisissez ...
     * @param unknown_type $sanschoix
     */
    public function __construct($options = array())
    {
        if (is_array($options)) {
            if (array_key_exists('sanschoix', $options)) {
                $this->_sanschoix = $options['sanschoix'];
            } else {
                $this->_sanschoix = '?';
            }
        } else {
            $this->_sanschoix = empty($options) ? '?' : $options;
        }
    }
    public function isValid($value)
    {
        if ($value == $this->_sanschoix) {
            $this->_error(self::INVALID);
            return false;
        }
        return true;
    }
}