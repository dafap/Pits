<?php
/**
 * Application PiTS
 *
 * Passerelle vers le table tarifs
 *
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Db/Table/Abstract.php';

/**
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     pomirol
 */
class TTarifs extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "tarifs";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "CodeTarif";

    /**
     * Nom de la classe des enregistrements
     *
     * @var string
     */
//    protected $_rowClass = 'Pits_Db_Table_Row';
    
    /**
     * Nom de la classe des jeux d'enregistrements
     *
     * @var string
     */
//    protected $_rowsetClass = 'Pits_Db_Table_Rowset';

    /**
     * Liaisons avec la table rythmesdepaiement
     *
     * @var array() unknown_type
     */
    protected $_referenceMap = array(
								'Rythme' => array(
											'columns' => 'CodeRythme',
											'refTableClass' => 'TRythmesdepaiement',
    ),
    );

    /**
     * Retourne le tableau des CodeTarifs de la table (filtrage possible par where)
     * @return array()
     */
    public function getArrayCodeTarifPermis($where = 1)
    {
        $tarifs = $this->fetchAll($this->select()
                         ->from($this, array('CodeTarif'))
                         ->where($where));
        $result = array();
        foreach ($tarifs as $tarif) {
            $result[] = $tarif->CodeTarif;
        }
        return $result;
    }
    /**
     * Retourne un tableau sérialisé à trois dimensions
     * - dimension 1 : TypeTarif (collège, lycée)
     * - dimension 2 : rang séquentiel du tarif dans ce type
     * - dimension 3 : tableau (CodeTarif, Libelle, Tarif, Prelevement)
     * @return string
     */
    public function getTarifsForJavaScript()
    {
        $tarifs = $this->fetchAll($this->select()
        ->from($this,array('CodeTarif','TypeTarif', 'Libelle', 'Tarif','Prelevement',))
        ->order(array('TypeTarif', 'CodeRythme', 'Prelevement',))
        );
        // $result est un tableau structuré selon TypeTarif (1:collège, 2:lycée)
        $result = array();
        $temoin_typeTarif = 0; // différent des valeurs prises donc on rentre dans if
        foreach ($tarifs as $tarif) {
            $typeTarif = $tarif->TypeTarif;
            if ($temoin_typeTarif != $typeTarif) {
                // chaque ligne de $result est un tableau séquentiel (index: $rang)
                $result[$typeTarif] = array();
                $temoin_typeTarif = $typeTarif;
            }
            // chaque ligne du tableau $result[$rang] est tableau (CodeTarif, Libelle, Tarif, Prelevement)
            $result[$typeTarif][] = array($tarif->CodeTarif, $tarif->Libelle, $tarif->Tarif, $tarif->Prelevement,);
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
    /**
     * Retourne un booleen Vrai / Faux selon si le tarif est de type prélèvement (Prelevement == 1)
     * @param string|integer codeTarif
     * @return boolean
     */
    public function isPrelevement($codeTarif)
    {
        if ((int) $codeTarif > 0) {
            $record = $this->find($codeTarif)->current();
            return $record->Prelevement == 1;
        }
        return false;
    }
}