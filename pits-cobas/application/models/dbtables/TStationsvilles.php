<?php
/**
 * Passerelle pour la vue `stationsvilles`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 28 mai 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TStationsvilles extends Zend_Db_Table_Abstract
{
    protected $_name = 'stationsvilles';
    protected $_primary = 'CodeStation';

    /**
     * Retourne un tableau des CodeStation hors COBAS serialisé pour JavaScript
     *
     * @return string
     */
    public function getStationsHorsCobasForJavaScript()
    {
        $records = $this->fetchAll($this->select()
        ->from($this, array('CodeStation'))
        ->where('COBAS = ?', 0)
        ->order('CodeStation'));
        $result = array();
        foreach ($records as $record) {
            $result[] = $record->CodeStation;
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
    /**
     * Renvoie un tableau de la forme array('CodeStation' => ..., 'Station' => 'nomVille - nomStation')
     * Filtre éventuellement selon $commune qui est le code Insee de la commune recherchée
     * 
     * @param optionnal string(5) $commune
     * @return array('CodeStation' => int, 'Station' => string)
     */
    public function getListeStations($commune = null) 
    {
        $where = $this->select()->order(array('NomVille', 'NomStation'));
        if (! is_null($commune)) {
            $where = $where->where('codeInsee = ?', $commune);
        }
        $result = array();
        foreach ($this->fetchAll($where) as $record) {
            $result[$record->CodeStation] = $record->NomVille . ' - ' . $record->NomStation;
        }
        return $result;
    }
}