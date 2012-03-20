<?php
/**
 * Passerelle pour la table `villes`
 *
 * @project télé-paiement CCM
 * @package application/models/dbtables
 * @author  DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date    20 mai 2011 - 19/09/2011
 * @version $Id: $
 */

class Tp_Model_DbTable_TVilles extends Zend_Db_Table_Abstract
{
    protected $_name = "villes";
    protected $_primary = "CodeInsee";
    /**
     * Retourne la liste des villes répondant au critère indiqué par $where, triée par `Nom`
     * 
     * @param string|array|Zend_Db_Table_Select $where
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function liste($where = "CCM=1") {
        $result = array();
        $villes = $this->fetchAll($where, 'Nom');
        foreach ($villes as $ville) {
            $result[$ville->CodeInsee] = $ville->Nom;
        }
        return $result;
    }
    /**
     * Retourne la liste des villes de la CCM
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function ccm() 
    {
        return $this->liste("CCM=1");
    }
    /**
     * Retourne la liste des villes de la zone HorsCCM (canton de Podensac)
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function horsCcm() 
    {
        return $this->liste("HorsCCM=1");
    }
    /**
     * Retourne la liste des villes de a zone des TS (CCM et HorsCCM)
     * @return array(`codeInsee` => `Nom`, ...)
     */
    public function zone()
    {
        return $this->liste("CCM=1 Or HorsCCM=1");
    }
    /**
     * Retourne la ville correspondant à un code INSEE
     * 
     * @param string codeInsee
     * @return string
     */
    public function nom($codeInsee) 
    {
        $where = $this->select()->where('CodeInsee = ?', $codeInsee);
        $ville = $this->fetchAll($where)->current();
        return $ville->Nom;
    }
}