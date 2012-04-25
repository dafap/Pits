<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 24 avril 2012
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2012, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.3.0
 * @since      Ce fichier est disponible depuis la version 0.3.0
 */

class Pits_Validate_Age extends Zend_Validate_Abstract
{
    const TROP_JEUNE = 'tropJeune';
    protected $_messageTemplates = array(
            self::TROP_JEUNE => "Cet élève est trop jeune pour être inscrit.",
            );
    private $_ageMini;
    private $_dateRentree;
    
    public function __construct($ageMini)
    {
        $this->_ageMini = $ageMini;
        list($anneeRentree, $anneeSortie) = split('-', Zend_Controller_Front::getInstance()->getParam('config')->annee->scolaire);
        $this->_dateRentree = array('jour' => 1, 'mois' => 9, 'an' => (int) $anneeRentree);
    }
    public function isValid($value)
    {
        list($jourN, $moisN, $anneeN) = split('/', $value);
        $annees = $this->_dateRentree['an'] - $anneeN;
        if ($this->_dateRentree['mois'] <= $moisN) {
            if ($this->_dateRentree['mois'] == $moisN) {
                if ($this->_dateRentree['jour'] < $jourN) $annees--;
            } else {
                $annees--;
            }
        }
        if ($annees < $this->_ageMini) {
            $this->_error(self::TROP_JEUNE);
            return false;
        }
        return true;
    }
}