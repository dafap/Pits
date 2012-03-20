<?php
/**
* Passerelle pour la table `tarifs`
* 
* @project pits
* @package application/models/dbtables
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 27 mai 2011
* @version $Id: $
*/

class Pits_Model_DbTable_TTarifs extends Zend_Db_Table_Abstract
{
    protected $_name = 'tarifs';
    protected $_primary = 'CodeTarif';
    
    /**
     * Retourne un tableau sérialisé à trois dimensions
     * - dimension 1 : TypeTarif 
     * - dimension 2 : rang séquentiel du tarif dans ce type
     * - dimension 3 : tableau (CodeTarif, Libelle, Tarif, )
     * 
     * @return string
     */
    public function getTarifsForJavaScript()
    {
        $tarifs = $this->fetchAll($this->select()
        ->from($this,array('CodeTarif', 'TypeTarif', 'Libelle', 'Tarif', /*'CodeRythme', 'ModeDePaiement',*/))
        ->order(array('TypeTarif', 'CodeRythme', /*'ModeDePaiement',*/))
        );
        // $result est un tableau structuré selon TypeTarif (voir table)
        $result = array();
        foreach ($tarifs as $tarif) {
            $typeTarif = $tarif->TypeTarif;
            if (!array_key_exists($typeTarif, $result)) {
                // chaque ligne de $result est un tableau séquentiel (index: $rang)
                $result[$typeTarif] = array();
            }
            // chaque ligne du tableau $result[$typeTarif][$rang] est tableau (CodeTarif, Libelle, Tarif, )
            $result[$typeTarif][] = array($tarif->CodeTarif, $tarif->Libelle, $tarif->Tarif, );
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
    
}