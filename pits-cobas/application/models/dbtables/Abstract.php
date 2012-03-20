<?php
/**
* Classe abstraite pour les passerelles du projet
* 
* @project pits
* @package package_name
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 24 mai 2011
* @version $Id: $
*/

abstract class Pits_Model_DbTable_Abstract extends Zend_Db_Table_Abstract
{
    /**
     * Retourne le tableau des champs
     * 
     * @return array 
     */
    public function getFields() 
    {
        return $this->_cols;
    }
}