<?php
/**
* Passerelle pour la vue `stationsdispo`
* 
* @project pits
* @package application/models/dbtables
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 27 mai 2011
* @version $Id: $
*/

class Pits_Model_DbTable_TStationsdispo extends Zend_Db_Table_Abstract
{
    protected $_name = 'stationsdispo';
    protected $_primary = 'CodeStation'; // fictif
    
    /**
     * Retourne un tableau sérialisé à trois dimensions
     * - dimension 1 : TypeTarif 
     * - dimension 2 : rang séquentiel du tarif dans ce type
     * - dimension 3 : tableau (CodeTarif, Libelle, Tarif, )
     * 
     * @return string
     */
    public function getStationsdispoForJavaScript()
    {
        $stations = $this->fetchAll($this->select()
// tout        ->from($this,array('CodeEN', 'CodeInsee', 'CodeStation', 'NomLongStation',))
        ->order(array('CodeEN', 'NomLongStation',))
        );
        // $result est un tableau structuré selon CodeEN (voir table)
        $result = array();
        foreach ($stations as $station) {
            $codeEN = $station->CodeEN;
            if (!array_key_exists($codeEN, $result)) {
                // chaque ligne de $result est un tableau séquentiel (index: $rang)
                $result[$codeEN] = array();
            }
            // chaque ligne du tableau $result[$typeTarif][$rang] est tableau (CodeStation, NomLongStation )
            $result[$codeEN][] = array($station->CodeStation, $station->NomLongStation, );
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
    
}