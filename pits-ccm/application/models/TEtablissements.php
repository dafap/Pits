<?php
/**
 * Application PiTS
 *
 * Passerelle vers le table etablissements
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

/**
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     pomirol
 */
class TEtablissements extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "etablissements";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "CodeEN";

    /**
     * Liaisons avec la table villes
     *
     * @var array() unknown_type
     */
    protected $_referenceMap = array(
								'Ville' => array(
											'columns' => 'Commune',
											'refTableClass' => 'TVilles',
    ),
    );

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
     * Renvoie la liste des établissements
     */
    public function liste() 
    {
        $etabls = $this->fetchAll($this->select()->from($this, array('CodeEN', 'Nom', 'Commune', 'visible')));
        return  $etabls->toArray();
    }
    /**
     * Retourne un rowset des établissements de la ville $codeInsee
     *
     * @param string(5) $codeInsee
     * @return object TEtablissements
     */
    public function getByVille($codeInsee, $visible = 1)
    {
        return $this->fetchAll($this->select()
        ->from($this,array('CodeEN', 'Nom', 'visible'))
        ->where('Commune = ?', $codeInsee)
        ->where('visible = ?', $visible)
        ->order('Nom'));
    }

    /**
     * Retourne un tableau sérialisé de (CodeEN,TypeTarif)
     *
     * @return string
     */
    public function getTypeTarifForJavaScript()
    {
        $etablissements = $this->fetchAll($this->select()
        ->from($this, array('CodeEN', 'TypeTarif')));
        // $result est un tableau (CodeEN,TypeTarif)
        $result = array();
        foreach ($etablissements as $etablissement) {
            $result[] = array($etablissement->CodeEN, $etablissement->TypeTarif);
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
}