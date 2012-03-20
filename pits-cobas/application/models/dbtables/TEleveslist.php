<?php
/**
 * Passerelle pour la table 'eleveslist`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 22 mai 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TEleveslist extends Zend_Db_Table_Abstract
{
    protected $_name = 'eleveslist';
    protected $_primary = 'eleveId';
    
    /**
     * Retourne le montant en centimes dû par le parent $userId et la référence de la commande dans un tableau
     * 
     * @param int $userId
     * @return array
     */
    public function montantRef($userId) 
    {
        $select = $this->select()->from($this, array('eleveId','TarifTarif'))->where('userId = ?', $userId)->where('fichevalidee = 0');
        $montant = 0.0;
        $maref = $userId;
        $enfants = $this->fetchAll($select);
        foreach ($enfants as $enfant) {
            $montant += $enfant->TarifTarif;
            $maref .= '|' . $enfant->eleveId;
        }
        $montant *= 100;
        $maref = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss') . '|' . $montant . '|'. $maref;
        return array('montant' => $montant, 'maref' => $maref);
    }
    public function statparetab() 
    {
        $select = $this->select()
        ->from($this, array('ville' => 'VilleEcole', 'ecole' => 'NomEcole', 'nb' => 'count(eleveId)'))
        ->group(array('VilleEcole', 'NomEcole'));
        return $this->fetchAll($select);
    }
    public function statparstation() 
    {
        $select = $this->select()
        ->from($this, array('ville' => 'VilleStation1', 'station' => 'NomStation1', 'nb' => 'count(eleveId)'))
        ->group(array('VilleStation1', 'NomStation1'));
        return $this->fetchAll($select);
    }
    public function statparville() 
    {
        $select = $this->select()
        ->from($this, array('ville' => 'NomVilleR1', 'nb' => 'count(eleveId)'))
        ->group('NomVilleR1');
        return $this->fetchAll($select);
    }
}