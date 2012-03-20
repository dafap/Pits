<?php
/**
 * Passerelle pour la table `etablissements`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 21 mai 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TEtablissements extends Zend_Db_Table_Abstract
{
    protected $_name = "etablissements";
    protected $_primary = "CodeEN";
    protected $_referenceMap = array(
        'ville' => array('columns' => 'Commune', 'refTableClass' => 'Pits_Model_DbTable_TVilles',)
    );

    public function getEcolesArcachonForJavaScript() {
        $records = $this->fetchAll($this->select()
        ->from($this, array('CodeEN'))
        ->where('Commune = ?', '33009')
        ->order('CodeEN'));
        $result = array();
        foreach ($records as $record) {
            $result[] = $record->CodeEN;
        }
        // On serialise le tableau obtenu pour traitement par JavaScript
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
}