<?php
/**
 * Passerelle pour la table des circuits
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 20 juin 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TCircuits extends Zend_Db_Table_Abstract
{
    protected $_name = 'circuits';
    protected $_primary = 'CodeCircuit';
    protected $_referenceMap = array(
       'service' => array('columns' => 'CodeService', 'refTableClass' => 'Pits_Model_DbTable_TServices',),
       'station' => array('columns' => 'CodeStation', 'refTableClass' => 'Pits_Model_DbTable_TStations',),
    );
    /**
     * CodeCircuit n'étant pas déclaré comme autoincrement dans PiTS, renvoie le prochain numéro
     */
    public function nextCodeCircuit() 
    {
        $where = $this->select()->from($this, array('lastCodeCircuit' => 'max(CodeCircuit)',));
        return $this->fetchAll($where)->current()->lastCodeCircuit + 1;
    }
}