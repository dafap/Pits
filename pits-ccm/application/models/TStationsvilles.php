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
class TStationsvilles extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "stationsvilles";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "codeInsee";

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
     * Retourne le tableau des CodeStation de la table (filtrage possible par where)
     * @return array()
     */
    public function getArrayCodeStationPermis($where = 1)
    {
        $stations = $this->fetchAll($this->select()
                         ->from($this, array('CodeStation'))
                         ->where($where));
        $result = array();
        foreach ($stations as $station) {
            $result[] = $station->CodeStation;
        }
        return $result;
    }
    /**
     * Retourne un tableau sérialisé à quatre dimensions
     * - dimension 1 : regroupement d'une commune
     * - dimension 2 : index 0 : tableau (codeInsee, nomCommune)
     * -             : index 1 : tableau des stations de la commune
     * @return string
     */
    public function getStationsForJavaScript()
    {
        $stations = $this->fetchAll($this->select());
        // $result est un tableau structuré selon le rang de la commune
        $result = array();
        $temoin_commune = '123456'; // codeInsee a 5 caractères donc on rentre dans if
        $iv = -1;
        foreach ($stations as $station) {
            $codeInsee = $station->codeInsee;
            if ($temoin_commune != $codeInsee) {
                $iv++;
                // chaque ligne de $result est un tableau à deux dimensions
                // - index1 : $rang ; index2 : 0 (tableau de la commune) ou 1 (tableau des stations)
                $result[$iv][0] = array($codeInsee, $station->NomVille);
                $result[$iv][1] = array(); // tableau des stations (voir ci-dessous)
                $temoin_commune = $codeInsee;
            }
            // chaque ligne du tableau $result[$iv][1] est le tableau (CodeStation,NomStation)
            $result[$iv][1][] = array($station->CodeStation, $station->NomStation);
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
}