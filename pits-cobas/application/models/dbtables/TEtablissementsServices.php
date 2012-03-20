<?php
/**
 * Passerelle pour la table `etablissementsservices`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 20 juin 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TEtablissementsServices extends Zend_Db_Table_Abstract
{
    protected $_name = 'etablissementsservices';
    protected $_primary = array('CodeEN', 'CodeService');
    protected $_referenceMap = array(
       'etablissement' => array('columns' => 'CodeEN', 'refTableClass' => 'Pits_Model_DbTable_TEtablissements',),
       'service' => array('columns' => 'CodeService', 'refTableClass' => 'Pits_Model_DbTable_TServices',),
       'villeecole' => array('columns' => 'CodeEN', 'refTableClass' => 'Pits_Model_DbTable_TVillesecoles',),
    );
}