<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 2 août 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits/validate
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */
require_once 'Zend/Validate/Abstract.php';
/**
 * @category   pits
 * @package    library
 * @subpackage pits/validate
 * @author     pomirol
 */
class Pits_Validate_ElvDejaInscrit extends Zend_Validate_Abstract
{
    const ELVEXISTE = 'elvexiste';
    protected $_messageTemplates = array(
    self::ELVEXISTE => "L'enfant %value% est déjà inscrit !",
    );
    private $_force;
    private $_prenom;
    private $_dateN;
    public function __construct($pk, $prenom, $dateN)
    {
        // si pk == 0 (primary key), nouvel eleve donc on regarde s'il n'est pas present
        // et on force la comparaison
        $this->_force  = $pk == 0;
        $this->_prenom = $prenom;
        $this->_dateN  = $dateN;
    }
    public function isValid($value)
    {
        if ($this->_force) {
            //$this->_setValue((string) $value);
            $televes = new TEleves();
            if ($televes->dejaInscrit($value, $this->_prenom, $this->_dateN)) {
                // pas de validation
                $this->_error(self::ELVEXISTE, strtoupper($this->_prenom . " " . $value));
                return false;
            }
        }
        return true;
    }
}