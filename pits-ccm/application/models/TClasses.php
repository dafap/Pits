<?php
/**
 * Application PiTS
 *
 * Passerelle vers le table classes
 *
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Db/Table/Abstract.php';

class TClasses extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "classes";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "CodeClasse";
    
    /**
     * Nom de la classe des enregistrements
     *
     * @var string
     */
//    protected $_rowClass = 'Pits_Db_Table_Row';
    
    /**
     * Nom de la classe des jeux d'enregistrements
     *
     * @var string
     */
//    protected $_rowsetClass = 'Pits_Db_Table_Rowset';

    /**
     * Lecture de la table pour rendre un tableau associatif CodeInsee => Nom
     * qui va notamment servir à construire les listes déroulantes des formulaires.
     *
     * @return unknown_type
     */
    public function liste() {
        $result = array();
        $classes = $this->fetchAll(1,'Nom');
        foreach ($classes as $classe) {
            $result[$classe->CodeClasse] = $classe->Nom;
        }
        return $result;
    }
}