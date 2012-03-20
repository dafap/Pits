<?php
/**
 * Passerelle pour la table `payboc`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 3 juin 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TPaybox extends Zend_Db_Table_Abstract
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
    
    public function nbPaybox() {
        $where = $this->select()->from($this, array('nbPaybox' => 'count(*)'));
        $record = $this->fetchAll($where)->current();
        return $record['nbPaybox'];
    }
    public function totalPaybox() {
        $where = $this->select()->from($this, array('totalPaybox' => 'sum(montant)'));
        $record = $this->fetchAll($where)->current();
        return (float) $record['totalPaybox'] / 100;
    }
}