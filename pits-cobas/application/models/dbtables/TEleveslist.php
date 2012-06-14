<?php
/**
 * Passerelle pour la table 'eleveslist`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 22 mai 2011 (modifié le 12 juin 2012)
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
    /**
     * Retourne un rowset
     * 
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function statparetab()
    {
        $select = $this->select()
        ->from($this, array('ville' => 'VilleEcole', 'ecole' => 'NomEcole', 'nb' => 'count(eleveId)'))
        ->group(array('VilleEcole', 'NomEcole'));
        return $this->fetchAll($select);
    }
    /**
     * Retourne un rowset
     * 
     * Attention (12/06/2012) :
     *  - $dest prend les valeurs "1" (pour destination principale) ou "2" (pour garde alternée)
     *  - $jour prend les valeurs "" (pour lu ma je ve), "m" (pour mercredi), "s" (pour samedi)
     *  
     * @param string $dest
     * @param string $jour
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function statparstation($dest = "1", $jour = "")
    {
        switch ($jour) {
            case "m":
                $select = $this->select()
                ->from($this, array('ville' => 'VilleStation' . $dest .'m', 'station' => 'NomStation' . $dest . 'm', 'nb' => 'count(eleveId)'))
                ->group(array('VilleStation' . $dest . 'm', 'NomStation' . $dest . 'm'))
                ->having("NomStation". $dest . "m IS NOT NULL");
                break;
            case "s":
                $select = $this->select()
                ->from($this, array('ville' => 'VilleStation' . $dest . 's', 'station' => 'NomStation' . $dest . 's', 'nb' => 'count(eleveId)'))
                ->group(array('VilleStation' . $dest . 's', 'NomStation' . $dest . 's'))
                ->having("NomStation" . $dest . "s IS NOT NULL");
                break;
            default:
                $select = $this->select()
                ->from($this, array('ville' => 'VilleStation' . $dest, 'station' => 'NomStation' . $dest, 'nb' => 'count(eleveId)'))
                ->group(array('VilleStation' . $dest, 'NomStation' . $dest))
                ->having("NomStation" . $dest . " IS NOT NULL");
            break;
        }
        return $this->fetchAll($select);
    }
    /**
     * Retourne un rowset
     * 
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function statparville()
    {
        $select = $this->select()
        ->from($this, array('ville' => 'NomVilleR1', 'nb' => 'count(eleveId)'))
        ->group('NomVilleR1');
        return $this->fetchAll($select);
    }
}