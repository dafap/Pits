<?php
/**
* Passerelle pour la vue `villesecoles`
*
* @project pits
* @package application/models/dbtables
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 25 mai 2011
* @version $Id: $
*/

class Pits_Model_DbTable_TVillesecoles extends Zend_Db_Table_Abstract
{
    protected $_name = "villesecoles";
    protected $_primary = "CodeEN";
    
    public function liste() 
    {
        $result = array();
        $ecoles = $this->fetchAll($this->select()->order('Nom'));
        foreach ($ecoles as $ecole) {
            $result[$ecole->CodeEN] = $ecole->Nom;
        }
        return $result;
    }
}