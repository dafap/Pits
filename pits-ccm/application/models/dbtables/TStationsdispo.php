<?php
/**
 * Passerelle pour la vue `tstationsdispo`
 *
 * @project project_name
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 6 juin 2011
 * @version $Id: $
 */

class Pits_DbTable_TStationsdispo extends Zend_Db_Table_Abstract
{
    protected $_name = 'stationsdispo';
    protected $_primary = 'CodeStation'; // fictif

    /**
     * Retourne un tableau sérialisé à quatre dimensions
     * - dimension 1 : regroupement d'un établissement scolaire
     * - dimension 2 : regroupement d'une commune
     * - dimension 3 : liste séquentielle
     * - dimension 4 : tableau décrivant une station (codeStation, nomLongStation)
     * @return string
     */
    public function getStationsForJavaScript()
    {
        $stations = $this->fetchAll($this->select());
        // $result est un tableau structuré selon le rang de la commune
        $result = array();
        foreach ($stations as $station) {
            $codeEN = $station->CodeEN;
            $codeVille = $station->CodeInsee;
            if (!array_key_exists($codeEN, $result)) {
                $result[$codeEN] = array();
            }
            if (!array_key_exists($codeVille, $result[$codeEN])) {
                $result[$codeEN][$codeVille] = array();
            }
            // chaque ligne du tableau $result[$codeEN][$codeInsee] est un tableau (codeStation, nomLongStation)
            $result[$codeEN][$codeVille][] = array($station->CodeStation, $station->NomLongStation);
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
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
}
