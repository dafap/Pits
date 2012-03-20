<?php
/**
 * Application PiTS
 *
 * Passerelle vers le table stations
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
class TStations extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "stations";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "CodeStation";

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
     * Retourne un rowset des stations de la ville $codeInsee
     *
     * @param string(5) $codeInsee
     * @return object TStations
     */
    public function getByVille($codeInsee)
    {
        return $this->fetchAll($this->select()
        ->from($this,array('CodeStation', 'Nom'))
        ->where('garder = 1 And Commune = ?', $codeInsee)
        ->order('Nom'));
    }
    
    /**
     * Retourne un tableau sérialisé à trois dimensions
     * - dimension 1 : regroupement d'une commune
     * - dimension 2 : index 0 : tableau (codeInsee, nomCommune)
     * -             : index 1 : tableau des nomStation de la commune
     * @return string
     */
    public function getStationsForJavaScript()
    {
        $stations = $this->fetchAll($this->select()
        ->from($this, array('codeInsee' => 'Commune', 'NomStation' => 'Nom'))
        ->where('garder = 1')
        ->order(array('codeInsee', 'NomStation'))
        );
        return;
        // $result est un tableau structuré selon le rang de la commune
        $result = array();
        $temoin_commune = 0;
        $iv = 0;
        foreach ($stations as $station) {
            $codeInsee = $station->codeInsee;
            if ($temoin_commune != $codeInsee) {
                // chaque ligne de $result est un tableau à deux dimensions(index: $rang)
                $result[$iv][0] = array($codeInsee, $station->NomVille);
                $result[$iv][1] = array();
                $rang = 0;
                $temoin_commune = $codeInsee;
                $iv++;
            }
            // chaque ligne du tableau $result[$rang] est la chaine NomStation
            $result[$codeInsee][$rang] = $station->NomStation;
            $rang++;
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
}