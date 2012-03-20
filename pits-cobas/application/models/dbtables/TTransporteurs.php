<?php
/**
 * Passerelle pour la table des transporteurs
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 20 juin 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TTransporteurs extends Zend_Db_Table_Abstract
{
    protected $_name = 'transporteurs';
    protected $_primary = 'CodeTransporteur';
    protected $_referenceMap = array(
       'ville' => array('columns' => 'Commune', 'refTableClass' => 'Pits_Model_DbTable_TVilles',),
    );
}