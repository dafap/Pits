<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 18 mai 2010
 * 
 * @category   pits
 * @package    library
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
       self::INVALID => 'Vous devez choisir une valeur dans la liste',
       );
    private $_sanschoix;
    private $_haystack;
    private $_actif;
    
    /**
     * Place la valeur interdite correspondant à la ligne 1 --- Choisissez ...
     * @param unknown_type $sanschoix
     */
    public function __construct($sanschoix = '?', $haystack = array(), $actif = true)
    {        
        if (!is_array($haystack)) {
            require_once 'Pits/Validate/Exception.php';
            throw new Zend_Validate_Exception(__METHOD__ . ' : \'Array\' attendu comme paramètre !');
        } else {
            $this->setValeurInterdite($sanschoix);
            $this->setHaystack($haystack);
            $this->setActif($actif);
        }
    }
    public function setHaystack($haystack) {
        $this->_haystack = $haystack;
        return $this;
    }
    public function getHaystack() {
        return $this->_haystack;
    }
    public function setValeurInterdite($sanschoix) 
    {
        $this->_sanschoix = $sanschoix;
        return $this;
    }
    public function getValeurInterdite()
    {
        return $this->_sanschoix;
    }
    public function setActif($actif) {
        $this->_actif = $actif;
        return $this;
    }
    public function getActif() 
    {
        return $this->_actif;
    }
    public function isValid($value)
    {
        if ($this->getActif()) {
            if ($value == $this->getValeurInterdite() || !in_array($value, $this->getHaystack())) {
                $this->_error(self::INVALID);
                return false;
            }
        } 
        return true;
    }
}