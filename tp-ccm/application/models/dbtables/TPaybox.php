<?php
/**
 * Passerelle pour la table `payboc`
 *
 * @project télé-paiement CCM
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 3 juin 2011 - rev. 19/09/2011
 * @version $Id: $
 */

class Tp_Model_DbTable_TPaybox extends Zend_Db_Table_Abstract
{
    protected $_name = 'paybox';
    protected $_primary = 'payboxId';
    /**
     * Liaisons avec la table user
     *
     * @var array() unknown_type
     */
    protected $_referenceMap = array(
								'User' => array(
											'columns' => 'userId',
											'refTableClass' => 'Pits_Model_DbTable_TUser',),
    );
    /**
     * Renvoie le nombre de paiements enregistrés
     * @return integer
     */
    public function nbPaybox() {
        $where = $this->select()->from($this, array('nbPaybox' => 'count(*)'));
        $record = $this->fetchAll($where)->current();
        return $record['nbPaybox'];
    }
    /**
     * Renvoie le total des paiements Paybox
     * @return numeric (en euros)
     */
    public function totalPaybox() {
        return $this->total($this->select());
    }
    /**
     * Renvoie le total des paiements répondant au $select fourni
     * 
     * @param Zend_Db_Select $select
     * @return numeric
     */
    public function total($select)
    {
        $sep = $where = '';
        foreach ($select->getPart(Zend_Db_Select::WHERE) as $value) {
            $where .= $sep . $value;
            $sep = ' ';
        }
        if (empty($where)) {
            $s = $this->select()->from($this, array('total' => 'SUM(montant)'));
        } else {
            $s = $this->select()->from($this, array('total' => 'SUM(montant)'))->where($where);
        }
        $record = $this->fetchAll($s)->current();
        return $this->fetchAll($s)->current()->total / 100;
    }
    /**
     * Renvoie la date du dernier paiement (format FR)
     * @return string
     */
    public function lastPaybox() 
    {
        $s = $this->select()->order(array('datetrans DESC', 'heuretrans DESC'));
        $record = $this->fetchAll($s)->current();
        return substr($record->datetrans, 0, 2) . '/'
             . substr($record->datetrans, 2, 2) . '/'
             . substr($record->datetrans, 4) . ' ' 
             . $record->heuretrans;
    }
    /**
     * Renvoie la date de dernière extraction (format date mysql)
     * @return string
     */
    public function lastExtraction() 
    {
        $s = $this->select()->from($this, array('lastExtraction' => 'max(dateExtraction)',));
        $record = $this->fetchAll($s)->current();
        return $record->lastExtraction;
    }
    /**
     * Marque les fiches dont la dateExtraction est nulle et celles postérieures à $depuis
     * 
     * @param string (format date mysql) $flag
     * @param null|string (format date mysql) $depuis
     */
    public function marquage($flag, $depuis = null) 
    {
        if (!is_null($depuis)) {
            $this->update(array('dateExtraction' => $flag), array('dateExtraction > ?' => $depuis));
        }
        $this->update(array('dateExtraction' => $flag), 'dateExtraction IS NULL');
    }
    /**
     * Compte le nombre de fiches dont la dateExtraction est égale à $flag
     * 
     * @param string(format date mysql) $flag
     * @return integer
     */
    public function compte($flag) 
    {
        $r = $this->fetchAll($this->select()->from($this, array('nb' => 'count(*)'))->where('dateExtraction = ?', $flag))
        ->current();
        return $r['nb'];
    }
}