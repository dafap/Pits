<?php
/**
 * Passerelle pour la table `factures`
 *
 * @project télé-paiement CCM
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 26 sept. 2011
 * @version $Id: $
 */

class Tp_Model_DbTable_TFactures extends Tp_Model_DbTable_Abstract
{
    protected $_name = 'factures';
    protected $_primary = 'factureId';
    protected $_rowClass = 'Tp_Model_DbTable_Row_TFacture';

    protected $_referenceMap = array(
      'user' => array('columns' => 'userId', 'refTableClass' => 'Tp_Model_DbTable_TUser',)
    );
    
    public function totalFactures()
    {
        $s = $this->select();
        return $this->total($s);
    }
    public function totalImpayes()
    {
        $select = $this->select()->where('datePaiement IS NULL');
        return $this->total($select);
    }
    public function total($select)
    {
        $sep = $where = '';
        foreach ($select->getPart(Zend_Db_Select::WHERE) as $value) {
            $where .= $sep . $value;
            $sep = ' ';
        }
        if (empty($where)) {
            $s = $this->select()->from($this, array('total' => 'SUM(montant)'));
        } else {
            $s = $this->select()->from($this, array('total' => 'SUM(montant)'))->where($where);
        }
        $record = $this->fetchAll($s)->current();
        return $this->fetchAll($s)->current()->total;
    }

    /**
     * Retourne le montant en centimes dû par l'utilisateur $userId et la référence de la commande dans un tableau
     *
     * @param int $userId
     * @param string $service
     * @return array
     */
    public function montantRef($userId, $service)
    {
        $select = $this->select()->from($this, array('factureId', 'montant'))->where('userId = ?', $userId)->where('datePaiement IS NULL');
        $montant = 0.0;
        $maref = $userId;
        $factures = $this->fetchAll($select);
        foreach ($factures as $facture) {
            $montant += $facture->montant;
            $maref .= '|' . $facture->factureId;
        }
        $montant *= 100;
        $maref = $service . '|' . Tp_Model_Format::date('YYYY-MM-dd HH:mm:ss') . '|' . $montant . '|'. $maref;
        return array('montant' => $montant, 'maref' => $maref);
    }
    /**
     * Valide les factures acquitées
     *
     * @param array $factureIds
     * @param string $datetrans
     * @param string $idtrans
     */
    public function validePaiement($factureIds, $datetrans, $idtrans)
    {
        for ($j=0; $j < count($factureIds); $j++) {
            $facture = $this->find($factureIds[$j])->current();
            $facture->valide($datetrans, $idtrans);
        }
    }
    /**
     * Renvoie les élements codeEleve, montant, anneeScolaire et exercice d'une facture dans un tableau
     * 
     * @param integer $factureId
     * @return array
     */
    public function getElementsFacture($factureId) 
    {
        $r = $this->find($factureId)->current();
        return array('codeEleve' => $r['codeEleve'], 'montant' => $r['montant'], 'anneeScolaire' => $r['anneeScolaire'], 'exercice' => $r['exercice'],);
    }
}