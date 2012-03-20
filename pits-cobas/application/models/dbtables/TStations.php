<?php
/**
* Passerelle pour la table 'stations'
*
* Description longue du fichier s'il y en a une
* 
* @project pits
* @package application/models/dbtables
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 20 juin 2011
* @version $Id: $
*/

class Pits_Model_DbTable_TStations extends Zend_Db_Table_Abstract
{
    protected $_name = 'stations';
    protected $_primary = 'CodeStation';
    protected $_referenceMap = array(
      'villes' => array('columns' => 'Commune', 'refTableClass' => 'Pits_Model_DbTable_TVilles',)
    );
}