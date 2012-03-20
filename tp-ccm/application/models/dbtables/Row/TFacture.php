<?php
/**
 * Classe étendant Zend_Db_Table_Row_Abstract pour la passerelle TUser
 *
 * Surcharge la méthode save pour gérer les champs previousIp, datePreviousLogin, nomSA et prenomSA
 *
 * @project télé-paiement CCM
 * @package application/models/dbtables/Row
 * @author  DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date    23 mai 2011 - rev. 19/09/2011
 * @version $Id: $
 */

class Tp_Model_DbTable_Row_TFacture extends Tp_Model_DbTable_Row_Abstract
{
    /**
     * Acquitte la facture
     * 
     * @param string $date
     * @param string $ref
     */
    public function valide($date, $ref)
    {
        $this->datePaiement = $date;
        $this->refPaiement = $ref;
        parent::save();
    }
}