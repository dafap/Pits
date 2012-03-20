<?php
/**
 * Passerelle pour la table `services`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 20 juin 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TServices extends Zend_Db_Table_Abstract
{
    protected $_name = 'services';
    protected $_primary = 'CodeService';
    protected $_referenceMap = array(
       'transporteur' => array('columns' => 'CodeTransporteur', 'refTableClass' => 'Pits_Model_DbTable_TTransporteurs',),
    );
}