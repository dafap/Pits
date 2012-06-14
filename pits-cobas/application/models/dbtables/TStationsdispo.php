<?php
/**
 * Passerelle pour la vue `stationsdispo`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 27 mai 2011 (modifié le 12 juin 2012 pour gérer les jours de circulation `lumajeve`, `me` et `sa`)
 * @version $Id: $
 */

class Pits_Model_DbTable_TStationsdispo extends Zend_Db_Table_Abstract
{
    protected $_name = 'stationsdispo';
    protected $_primary = 'CodeStation'; // fictif

    /**
     * Retourne un tableau sérialisé à quatre dimensions
     * - dimension 1 : CodeEN
     * - dimension 2 : jours concernés (0 pour lumajeve, 1 pour me, 2 pour sa)
     * - dimension 3 : rang séquentiel de la station dans ce type
     * - dimension 4 : tableau (CodeStation, NomLongStation, )
     *
     * @return string
     */
    public function getStationsdispoForJavaScript()
    {
        $stations = $this->fetchAll($this->select()
                // tout ->from($this,array(`CodeEN`, `CodeInsee`, `CodeStation`, `NomLongStation`, `lumajeve`, `me`, `sa`,))
                ->order(array('CodeEN', 'NomLongStation',))
        );
        // $result est un tableau structuré selon CodeEN (voir table)
        $result = array();
        foreach ($stations as $station) {
            $codeEN = $station->CodeEN;
            if (!array_key_exists($codeEN, $result)) {
                // chaque ligne de $result est un tableau de 3 tableaux (index: 1 pour `lumajeve`, 1 pour `me`, 2 pour `sa`)
                $result[$codeEN] = array(array(), array(), array());
            }
            // chaque ligne du tableau $result[$CodeEN][i][] est tableau (CodeStation, NomLongStation )
            if ($station->lumajeve == 1) {
                $result[$codeEN][0][] = array($station->CodeStation, $station->NomLongStation, );
            }
            if ($station->me == 1) {
                $result[$codeEN][1][] = array($station->CodeStation, $station->NomLongStation, );
            }
            if ($station->sa == 1) {
                $result[$codeEN][2][] = array($station->CodeStation, $station->NomLongStation, );
            }
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }

}