<?php
/**
 * Passerelle pour la table `villes`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 20 mai 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TVilles extends Zend_Db_Table_Abstract
{
    protected $_name = "villes";
    protected $_primary = "CodeInsee";
    /**
     * Retourne la liste des villes répondant au critère indiqué par $where, triée par `Nom`
     * 
     * @param string|array|Zend_Db_Table_Select $where
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function liste($where = "COBAS=1") {
        $result = array();
        $villes = $this->fetchAll($where,'Nom');
        foreach ($villes as $ville) {
            $result[$ville->CodeInsee] = $ville->Nom;
        }
        return $result;
    }
    /**
     * Retourne la liste des villes de la COBAS
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function cobas() 
    {
        return $this->liste("COBAS=1");
    }
    /**
     * Retourne la liste des villes de la zone HorsCOBAS (COBAN et Val de Leyre)
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function horsCobas() 
    {
        return $this->liste("HorsCOBAS=1");
    }
    /**
     * Retourne la liste des villes de a zone des TS (Cobas, Coban, Val de Leyre)
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function zone()
    {
        return $this->liste("COBAS=1 Or HorsCOBAS=1");
    }
}